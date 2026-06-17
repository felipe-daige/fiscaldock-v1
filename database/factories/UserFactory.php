<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Usuário',
            'sobrenome' => 'Teste',
            'telefone' => '(11) 99999-9999',
            'email' => 'usuario'.uniqid().'@example.com',
            'email_verified_at' => now(),
            'password' => 'password', // O cast 'hashed' do modelo já faz o hash automaticamente
            'remember_token' => Str::random(10),
            // LGPD fase 2.2: espelha um signup recente (versões em dia) — sem isto o
            // middleware RequireCurrentTerms redirecionaria todo usuário de teste pro re-aceite.
            'terms_version' => config('legal.terms_version', '1.0'),
            'privacy_version' => config('legal.privacy_version', '1.0'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuário com trial ativo — passa pelo gate de entitlements
     * (política "trial libera tudo"). Espelha o estado de um signup recente.
     */
    public function trialAtivo(int $creditos = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_used' => true,
            'trial_started_at' => now(),
            'trial_expires_at' => now()->addDays(30),
            'trial_credits_remaining' => $creditos,
        ]);
    }
}
