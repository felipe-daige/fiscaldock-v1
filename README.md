# FiscalDock

FiscalDock e uma plataforma SaaS brasileira de monitoramento fiscal e tributario para contadores, escritorios contabeis e equipes fiscais.

O produto foi desenhado para transformar arquivos fiscais em inteligencia acionavel: importar SPED e XML, monitorar participantes, executar consultas tributarias e acompanhar riscos fiscais com dashboards, alertas e fluxos em tempo real.

## O que o FiscalDock faz

- Importa arquivos SPED/EFD e extrai notas, participantes, itens, apuracoes e dados fiscais relevantes.
- Importa XMLs fiscais e consolida documentos para analise operacional e tributaria.
- Monitora participantes e fornecedores com apoio de consultas cadastrais e fiscais.
- Executa consultas tributarias em lote para reduzir verificacoes manuais.
- Exibe dashboards e BI fiscal para faturamento, compras, tributos, participantes e riscos.
- Centraliza alertas para identificar inconsistencias e situacoes que merecem acao antes de auditorias.

## Principais casos de uso

- Conferir fornecedores e participantes antes de tomar decisoes fiscais ou contabeis.
- Detectar riscos cadastrais e tributarios com antecedencia.
- Cruzar documentos importados com consultas externas e monitoramento continuo.
- Organizar analise fiscal por cliente, participante, periodo e documento.
- Dar visibilidade operacional para escritorios que precisam escalar a revisao fiscal.

## Stack

- Laravel 12
- PHP 8.3+
- PostgreSQL
- Vite
- Tailwind CSS v4
- Docker
- n8n
- SSE (Server-Sent Events)

## Como rodar localmente

### Opcao 1: ambiente local

Requer PHP, Composer, Node.js e acesso a um PostgreSQL configurado no `.env`.

```bash
composer setup
composer dev
```

Para gerar assets de producao:

```bash
npm run build
```

### Opcao 2: operacao com containers

Neste repositorio, comandos de aplicacao normalmente rodam dentro do container principal `fiscaldock-app-1`. O scheduler roda em `fiscaldock-scheduler-1`.

```bash
docker exec fiscaldock-app-1 php artisan about
docker exec fiscaldock-app-1 php artisan migrate --force
docker exec fiscaldock-app-1 php artisan test
docker exec fiscaldock-app-1 ./vendor/bin/pest
docker exec fiscaldock-app-1 ./vendor/bin/pint
```

## Comandos uteis

```bash
composer test
npm run build
docker exec fiscaldock-app-1 php artisan migrate:status
docker exec fiscaldock-app-1 php artisan queue:failed
docker exec fiscaldock-app-1 php artisan schedule:list
docker exec fiscaldock-app-1 php artisan pail
```

## Configuracao

- Comece a partir de `.env.example`.
- Configure a conexao com PostgreSQL antes de rodar `composer setup`.
- O fluxo operacional depende de webhooks e `API_TOKEN` para importacoes e consultas.
- Integracoes assincronas passam por n8n, que trabalha em conjunto com a aplicacao Laravel.

## Arquitetura em alto nivel

- `app/` concentra controllers, services, actions, models e regras de negocio.
- `resources/views/` contem as views publicas e autenticadas.
- `routes/` define rotas publicas, autenticadas, APIs internas e endpoints de progresso.
- `database/` concentra migrations, seeders e factories.
- O stack operacional inclui papeis separados para `app`, `worker` e `scheduler`.

## Observacoes importantes

- Nao crie novas migrations sem orientacao explicita dos mantenedores; este projeto trabalha com edicao do conjunto atual.
- `composer setup` executa `php artisan migrate --force`; confirme banco e ambiente antes de usar.
- `public/build/` e gerado, nao e codigo-fonte.
- O deploy nao depende apenas do container web; ha tambem processamento assincrono via worker e scheduler.

## Contribuicao

Contribuicoes sao bem-vindas, desde que preservem o contexto do produto e a consistencia fiscal do sistema.

Antes de abrir um PR:

```bash
composer test
docker exec fiscaldock-app-1 ./vendor/bin/pest
docker exec fiscaldock-app-1 ./vendor/bin/pint
```

Prefira mudancas pequenas, objetivas e coerentes com a arquitetura existente.
