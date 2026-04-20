<?php

use App\Http\Controllers\Api\DataReceiverController;
use Illuminate\Support\Facades\Route;

// ============================================
// Health Check (sem autenticação)
// ============================================
Route::get('/health', [DataReceiverController::class, 'health'])
    ->name('api.health');

// ============================================
// Importação EFD
// ============================================

// Recebe progresso de importação EFD (usado pelo n8n)
Route::post('/importacao/efd/progresso', [DataReceiverController::class, 'receiveImportacaoTxtProgress'])
    ->name('api.importacao.efd.progresso');

// Recebe progresso de extração de notas EFD por bloco (A, C, D)
// n8n envia fase + contadores por bloco; SSE lê e mescla no payload principal
Route::post('/importacao/efd/notas/progresso', [DataReceiverController::class, 'receiveNotasEfdProgress'])
    ->name('api.importacao.efd.notas.progresso');

// ============================================
// Importação XML
// ============================================

// Recebe progresso de importação de XMLs (NF-e, NFS-e, CT-e)
// n8n envia progresso para Laravel armazenar em cache (SSE lê do cache)
Route::post('/importacao/xml/progress', [DataReceiverController::class, 'receiveXmlImportacaoProgress'])
    ->name('api.importacao.xml.progress');

// ============================================
// Monitoramento
// ============================================

// Recebe resultado de consulta do Monitoramento
// n8n envia resultado da consulta (ou pode escrever diretamente no PostgreSQL)
Route::post('/monitoramento/consulta/resultado', [DataReceiverController::class, 'receiveMonitoramentoConsulta'])
    ->name('api.monitoramento.consulta.resultado');

// ============================================
// Consultas
// ============================================

// Endpoint unificado de progresso, conclusão e erro de consulta em lote
// n8n envia status=progresso (cache SSE), status=concluido (DB + cache) ou status=erro (refund + cache)
Route::post('/consultas/progresso', [DataReceiverController::class, 'receiveConsultasProgresso'])
    ->name('api.consultas.progresso');

