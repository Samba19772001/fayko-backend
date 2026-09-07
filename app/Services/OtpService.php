<?php

namespace App\Services;

use App\Models\User;
use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Génération, envoi et vérification des codes OTP.
 *
 * Utilisés à deux endroits du cahier des charges :
 *  - §3.1 : inscription / connexion
 *  - §3.5 : confirmation de signature électronique
 *
 * L'envoi SMS est abstrait derrière SMS_DRIVER (config/services.php) :
 *  - 'log' (dev) : le code est journalisé au lieu d'être envoyé.
 *  - 'africastalking' : envoi réel via Africa's Talking. Utilise le
 *    compte sandbox tant que le Sender ID de production n'est pas validé.
 */
class OtpService
{
    private const TTL_MINUTES = 5;
    private const MAX_TENTATIVES = 5; // rate limiting (§4.1)

    public function genererEtEnvoyer(User $user, string $contexte = 'connexion'): void
    {
        $code = (string) random_int(100000, 999999);
        $cleCache = $this->cle($user->id, $contexte);

        Cache::put($cleCache, [
            'hash' => Hash::make($code),
            'tentatives' => 0,
        ], now()->addMinutes(self::TTL_MINUTES));

        $this->envoyerSms($user->telephone, "Votre code Fayko : {$code} (valable ".self::TTL_MINUTES." min).");
    }

    public function verifier(User $user, string $code, string $contexte = 'connexion'): bool
    {
        $cleCache = $this->cle($user->id, $contexte);
        $entree = Cache::get($cleCache);

        if (! $entree) {
            return false;
        }

        if ($entree['tentatives'] >= self::MAX_TENTATIVES) {
            Cache::forget($cleCache);
            return false;
        }

        if (! Hash::check($code, $entree['hash'])) {
            $entree['tentatives']++;
            Cache::put($cleCache, $entree, now()->addMinutes(self::TTL_MINUTES));
            return false;
        }

        Cache::forget($cleCache);
        return true;
    }

    private function cle(int $userId, string $contexte): string
    {
        return "otp:{$contexte}:{$userId}";
    }

    private function envoyerSms(string $telephone, string $message): void
    {
        $driver = config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::info("[SMS -> {$telephone}] {$message}");
            return;
        }

        if ($driver === 'africastalking') {
            $this->envoyerViaAfricasTalking($telephone, $message);
            return;
        }

        throw new \RuntimeException("Fournisseur SMS '{$driver}' non implémenté.");
    }

    private function envoyerViaAfricasTalking(string $telephone, string $message): void
    {
        $config = config('services.sms.africastalking');

        $AT = new AfricasTalking($config['username'], $config['api_key']);
        $sms = $AT->sms();

        // Africa's Talking exige le format international complet (+221...).
        $numeroInternational = $this->formaterNumeroInternational($telephone);

        try {
            $resultat = $sms->send([
                'to' => $numeroInternational,
                'message' => $message,
                'from' => $config['sender_id'] ?: null,
            ]);
            Log::info('SMS envoyé via Africa\'s Talking', ['to' => $numeroInternational, 'reponse' => $resultat]);
        } catch (\Throwable $e) {
            // On journalise l'échec mais on ne bloque pas l'utilisateur : une
            // erreur d'envoi ne doit jamais empêcher la génération du code
            // (ex. le code reste valide en base même si le SMS échoue).
            Log::error('Échec envoi SMS Africa\'s Talking', ['to' => $numeroInternational, 'erreur' => $e->getMessage()]);
            throw new \RuntimeException("L'envoi du SMS a échoué. Réessayez dans quelques instants.");
        }
    }

    /**
     * Convertit un numéro local (ex. 770000001) au format international
     * attendu par Africa's Talking (+221770000001). Le Sénégal utilise
     * l'indicatif +221.
     */
    private function formaterNumeroInternational(string $telephone): string
    {
        $telephone = preg_replace('/\D/', '', $telephone); // ne garde que les chiffres

        if (str_starts_with($telephone, '221')) {
            return '+' . $telephone;
        }

        return '+221' . $telephone;
    }
}