# mod_gepeminfotutoria

Plugin Moodle para Moodle 4.2.11 que exibe, dentro da disciplina, contatos de Tutoria vinculados aos polos.

A experiência principal é inline, no estilo Label: a informação aparece diretamente na página da disciplina, sem exigir clique do estudante.

## Requisitos

- Moodle 4.2.11.
- Plugin instalado em `mod/gepeminfotutoria`.
- Papel Moodle `teacher` — Moderador, exibido publicamente como **Tutoria**.
- Polos representados por grupos do curso cujo nome contém `polo`.
- Branch estável: `MOODLE_402_STABLE`.

## Instalação

A pasta do plugin dentro do Moodle deve se chamar `gepeminfotutoria`.

Exemplo usando Git:

```bash
cd /caminho/do/moodle/mod
git clone -b MOODLE_402_STABLE https://github.com/UEMS-Virtual/moodle-mod_gepeminfotutoria.git gepeminfotutoria
cd /caminho/do/moodle
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

No ambiente Docker local deste projeto:

```bash
docker exec moodle42-app php /var/www/html/admin/cli/upgrade.php --non-interactive
docker exec moodle42-app php /var/www/html/admin/cli/purge_caches.php
```

## Como funciona

O plugin não cadastra tutores, contatos de tutoria ou polos. Ele lê dados já existentes no Moodle:

- usuários ativos matriculados na disciplina;
- papéis atribuídos no contexto da disciplina;
- grupos do curso usados como polos;
- foto de perfil do usuário;
- recurso nativo de mensagens do Moodle.

## Regras de domínio

### Tutoria

Usuário ativo da disciplina com papel Moodle `teacher` (Moderador). Na interface pública, a função aparece como **Tutoria**, sem especificar gênero nem modalidade presencial.

### Polo

Grupo da disciplina cujo nome contém `polo`, sem diferenciar maiúsculas/minúsculas. Números finais entre parênteses, como `(20)`, são ignorados na exibição, mas o vínculo interno usa o nome real do grupo no Moodle.

## Configurações da atividade

Ao adicionar a atividade na disciplina, é possível configurar:

- nome da atividade;
- descrição;
- título do painel do estudante.

A Tutoria é sempre esperada quando a atividade está presente.

Campos legados de expectativa de tutoria/mediação podem existir no banco ou em backups antigos, mas não são exibidos no formulário nem usados para decidir a interface.

## Visualização do estudante

O estudante vê primeiro a aba **Meu polo**, com:

- nome do seu polo;
- Tutoria vinculada ao seu polo;
- opção de alternar para **Lista completa**.

Se o estudante não estiver em nenhum polo, a aba **Meu polo** não usa a Lista completa como fallback, mas a Lista completa continua disponível.

## Visualização de professor/admin

Usuários com perfil de gestão da disciplina veem a **Lista completa**, com a equipe vinculada à disciplina inteira. A lista mostra pessoas e os polos atendidos por cada uma; não agrupa por polo.

## Estados vazios

Quando não há Tutoria vinculada:

- na Lista completa: `Tutoria não informada para a disciplina`;
- em Meu polo: `Tutoria não informada para seu polo`.

## Testes e validação

Comandos úteis para validação no ambiente Docker local:

```bash
# PHP lint do plugin.
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l

# PHPUnit.
docker exec moodle42-app bash -lc 'cd /var/www/html && vendor/bin/phpunit mod/gepeminfotutoria/tests/team_data_test.php mod/gepeminfotutoria/tests/output_test.php'

# Behat.
docker exec moodle42-app bash -lc 'cd /var/www/html && vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml --profile=chrome mod/gepeminfotutoria/tests/behat/inline_display.feature'

# Compilar AMD com Node 22 no host.
source ~/.nvm/nvm.sh
nvm use 22.22.3
cd /home/breno/docker/moodle42/moodle
npx grunt amd --root=mod/gepeminfotutoria
```

## Documentação complementar

- Norte do projeto: `docs/NORTE_DO_PROJETO.html`
- Explicação sobre Behat: `docs/BEHAT_NO_PROJETO.html`
- Glossário de domínio: `CONTEXT.md`
- Protótipos visuais: `docs/prototipos-visuais/`

## Branch Moodle

A branch estável para Moodle 4.2 é:

```text
MOODLE_402_STABLE
```
