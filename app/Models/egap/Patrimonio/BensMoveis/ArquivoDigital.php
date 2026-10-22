<?php

namespace App\Models\egap\Patrimonio\BensMoveis;

use App\Filament\Egap\Support\LegacyFileUrl;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArquivoDigital extends Model
{
    public const SITUACAO_PENDENTE = 0;

    public const SITUACAO_VALIDADO = 1;

    public const SITUACAO_INVALIDADO = 2;

    public const SITUACAO_CANCELADO = 3;

    protected $connection = 'emes';
    protected $table = 'egap_mat_arquivodigital';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'termo',
        'arquivo_digital',
        'atualizado_em',
        'atualizado_por',
        'data_validacao',
        'validado_por',
        'observacao',
        'situacao',
        'web',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'atualizado_em' => 'datetime',
        'data_validacao' => 'datetime',
        'situacao' => 'integer',
        'web' => 'integer',
    ];

    public function termoRel(): BelongsTo
    {
        return $this->belongsTo(Termo::class, 'termo', 'id');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'validado_por', 'id');
    }

    public static function caminhoArquivoDigitalNoDisco(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function normalizarCaminhoArquivoDigital(?string $path): ?string
    {
        $path = self::caminhoArquivoDigitalNoDisco($path);

        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'images/termos/')) {
            return '/'.$path;
        }

        return '/images/termos/'.ltrim($path, '/');
    }

    public function setArquivoDigitalAttribute(?string $path): void
    {
        $this->attributes['arquivo_digital'] = self::normalizarCaminhoArquivoDigital($path);
    }

    /**
     * URL de acesso ao arquivo. Se o arquivo existir no disco `egap` deste
     * projeto, aponta para cá. Caso contrário — caso dos registros antigos
     * que ainda guardam caminhos no padrão do sistema legado (`/images/...`)
     * e nunca foram migrados fisicamente para este disco —, aponta para a
     * URL do sistema antigo (`config('app.egap')` + `arquivo_digital`).
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => LegacyFileUrl::resolve($this->arquivo_digital, config('app.egap')),
        )->shouldCache();
    }

    public static function situacaoOptions(): array
    {
        return [
            self::SITUACAO_PENDENTE => 'Pendente',
            self::SITUACAO_VALIDADO => 'Validado',
            self::SITUACAO_INVALIDADO => 'Invalidado',
            self::SITUACAO_CANCELADO => 'Cancelado',
        ];
    }

    public static function situacaoLabel(int|string|null $situacao): string
    {
        if ($situacao === null || $situacao === '') {
            return 'Indefinido';
        }

        return self::situacaoOptions()[(int) $situacao] ?? 'Indefinido';
    }

    public static function situacaoColor(int|string|null $situacao): string
    {
        if ($situacao === null || $situacao === '') {
            return 'gray';
        }

        return match ((int) $situacao) {
            self::SITUACAO_VALIDADO => 'success',
            self::SITUACAO_INVALIDADO, self::SITUACAO_CANCELADO => 'danger',
            self::SITUACAO_PENDENTE => 'warning',
            default => 'gray',
        };
    }

    /**
     * Valida o termo: efetiva a transferência (Setor/Unidade dos bens) e marca o
     * arquivo digital como validado. Usado tanto pela validação manual feita pela
     * Seção de Patrimônio quanto pela assinatura eletrônica do setor destinatário
     * no Ambiente Externo.
     */
    public function validar(int $userId): bool
    {
        if ($this->situacao !== self::SITUACAO_PENDENTE || blank($this->termo)) {
            return false;
        }

        $transferencias = TransferenciaBemMovel::query()
            ->where('Termo', $this->termo)
            ->get([
                'Termo',
                'NumPatrimonio',
                'UnidadeAtual',
                'SetorAtual',
                'ComplementoAtual',
            ]);

        if ($transferencias->isEmpty()) {
            return false;
        }

        $this->getConnection()->transaction(function () use ($transferencias, $userId): void {
            foreach ($transferencias as $transferencia) {
                BemMovel::query()
                    ->whereKey($transferencia->NumPatrimonio)
                    ->update([
                        'UnidadeJudiciaria' => $transferencia->UnidadeAtual,
                        'Setor' => $transferencia->SetorAtual,
                        'ComplementoSetor' => $transferencia->ComplementoAtual,
                    ]);

                FasePedido::query()
                    ->where('id_termo', $transferencia->Termo)
                    ->update(['idSituacao' => 3]);
            }

            $this->fill([
                'atualizado_em' => now(),
                'data_validacao' => now(),
                'observacao' => null,
                'situacao' => self::SITUACAO_VALIDADO,
                'validado_por' => $userId,
            ])->save();
        });

        return true;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_em = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
