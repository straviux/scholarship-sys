<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

trait TokenValidation
{
    /**
     * Validate upload token expiry from database
     * 
     * Checks if token exists and is not past expiry date
     * 
     * @param string $token The token to validate
     * @param string $entityType Type of entity (disbursement, scholarship_record, requirement, etc)
     * @param \Illuminate\Database\Eloquent\Model|null $entity The entity to validate against
     * @return bool True if valid
     * @throws ValidationException
     */
    public function validateUploadToken(
        ?object $entity,
        string $entityType = 'general'
    ): bool {
        try {
            // Check if entity exists
            if (!$entity) {
                Log::warning('upload_token_validation_failed', [
                    'entity_type' => $entityType,
                    'reason' => 'Entity not found',
                ]);

                throw new \Exception('Entity not found');
            }

            // Check if entity has token expiry date
            if (!isset($entity->upload_token_expires_at) || !$entity->upload_token_expires_at) {
                Log::warning('upload_token_validation_failed', [
                    'entity_type' => $entityType,
                    'entity_id' => $entity->id ?? ($entity->disbursement_id ?? 'unknown'),
                    'reason' => 'No expiry date set',
                ]);

                throw new \Exception('Token not configured');
            }

            // Check if token has expired
            $expiry = $entity->upload_token_expires_at;
            if ($expiry->isPast()) {
                Log::warning('upload_token_expired', [
                    'entity_type' => $entityType,
                    'entity_id' => $entity->id ?? ($entity->disbursement_id ?? 'unknown'),
                    'expired_at' => $expiry->toIso8601String(),
                    'now' => now()->toIso8601String(),
                    'minutes_expired' => $expiry->diffInMinutes(now()),
                ]);

                throw new \Exception(
                    'Token expired at ' . $expiry->format('M d, Y H:i')
                );
            }

            // Success - log validation
            $this->debugLogTokenState($entity, $entityType, 'validated_successfully');

            return true;
        } catch (\Exception $e) {
            Log::warning('upload_token_validation_failed', [
                'entity_type' => $entityType,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            throw ValidationException::withMessages([
                'token' => 'Invalid or expired upload token: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Debug log token state (only in debug mode)
     * 
     * Helps with troubleshooting token issues without exposing full token
     */
    private function debugLogTokenState(
        object $entity,
        string $entityType,
        string $state
    ): void {
        if (!config('app.debug')) {
            return;
        }

        Log::debug('upload_token_' . $state, [
            'entity_type' => $entityType,
            'entity_id' => $entity->id ?? ($entity->disbursement_id ?? 'unknown'),
            'expires_at' => $entity->upload_token_expires_at?->toIso8601String(),
            'time_remaining' => $entity->upload_token_expires_at?->diffInMinutes(now()),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
