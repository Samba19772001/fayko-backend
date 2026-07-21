<?php

namespace App\Services;

use App\Models\User;
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
 * L'envoi SMS est abstrait derrière SMS_DRIVER (config/services.php) afin de
 * pouvoir brancher Twilio / Vonage / Orange SMS / etc. sans toucher au reste
 * de l'application. En développement (SMS_DRIVER=log), le code est simplement
 * journalisé au lieu d'être envoyé.
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
            return false; // expiré ou jamais généré
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

        Cache::forget($cleCache); // usage unique
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

        // TODO brancher le vrai fournisseur SMS ici (Twilio, Vonage, Orange SMS...)
        // en lisant SMS_API_KEY / SMS_API_SECRET / SMS_SENDER_ID depuis la config.
        throw new \RuntimeException("Fournisseur SMS '{$driver}' non implémenté.");
    }
}
