# Informações de Tutoria

Contexto do plugin Moodle que exibe, dentro de uma disciplina, contatos de tutoria vinculados aos polos.

## Language

**Disciplina**:
Curso Moodle onde a atividade de informações de tutoria está inserida.
_Avoid_: Sala, turma, course

**Polo**:
Grupo da Disciplina cujo nome contém a palavra “polo” e representa uma unidade/local de apoio do estudante; números finais entre parênteses no nome do grupo são informação operacional e não fazem parte do nome exibido.
_Avoid_: Unidade, grupo comum

**Tutor**:
Usuário ativo da Disciplina com papel Moodle `teacher` (Moderador), vinculado a um Polo por grupo; na interface pública, a função deve ser rotulada como “Tutoria” para evitar marcação de gênero e modalidade.
_Avoid_: Tutor Presencial, tutor da sala, moderador, Tutor como rótulo de interface

**Mediador Pedagógico**:
Função existente no plugin original, mas fora do escopo desta adaptação; não deve aparecer na interface nem orientar a busca de contatos.
_Avoid_: Mediador, professor mediador

**Equipe de Tutoria**:
Conjunto de Tutores ativos vinculados à Disciplina e aos Polos.
_Avoid_: Equipe de Tutoria e Mediação, Informações de tutoria, Equipe de tutoria e mediação pedagógica, Equipe de Tutoria Presencial

**Tutoria esperada**:
Regra da adaptação em que a presença de Tutor é sempre esperada quando a atividade está na Disciplina.
_Avoid_: Sala deve ter tutor, bloco obrigatório, tutor opcional

**Lista completa**:
Visualização que mostra a Equipe de Tutoria da Disciplina inteira como lista de pessoas com seus Polos atendidos.
_Avoid_: Visão institucional, todos, lista por polo

**Meu polo**:
Visualização do estudante que mostra apenas a Tutoria vinculada ao Polo do estudante.
_Avoid_: Minha sala, meu grupo

## Relationships

- Uma **Disciplina** possui zero ou mais **Polos**.
- Um estudante deve pertencer a no máximo um **Polo** por **Disciplina**.
- Uma **Disciplina** possui zero ou mais **Tutores**.
- Um **Tutor** é identificado somente pelo papel Moodle `teacher` na **Disciplina**.
- **Mediador Pedagógico** não participa da interface desta adaptação.
- Uma **Disciplina** sempre espera **Tutor** quando a atividade está presente.
- O formulário da atividade não pergunta se **Tutor** é esperado.
- Um **Polo** deve ter operacionalmente no máximo um **Tutor**, mas a interface mostra todos os Tutores vinculados se houver mais de um.
- A **Equipe de Tutoria** pertence a uma **Disciplina**.
- A **Lista completa** é calculada a partir da **Disciplina** inteira.
- **Meu polo** é calculado a partir do **Polo** do estudante.
- Se o estudante não pertence a nenhum **Polo**, **Meu polo** não mostra tutores como fallback, mas a **Lista completa** continua disponível para consulta.
- Se o estudante pertence a mais de um **Polo** na mesma **Disciplina**, isso é inconsistência operacional, mas a interface usa o primeiro Polo encontrado sem bloquear o estudante.
- Como **Tutor** é sempre esperado, a interface exibe mensagem de ausência quando não houver Tutor vinculado à **Disciplina** ou ao **Polo**.
- A experiência principal da atividade é inline na página da Disciplina; a página própria da atividade existe apenas como fallback técnico simples.

## Example dialogue

> **Dev:** “Quando não há Tutoria no Polo do estudante, mostramos a Equipe de Tutoria da Disciplina como fallback?”
> **Domain expert:** “Não dentro de Meu polo; a Lista completa continua disponível para consulta.”

## Flagged ambiguities

- “Sala” foi usado para se referir à **Disciplina**. Resolvido: a documentação deve usar **Disciplina**.
- “Tutor” é o conceito operacional, mas o rótulo público deve ser “Tutoria” para evitar marcação de gênero e modalidade.
- Um estudante em dois Polos na mesma Disciplina foi tratado como possibilidade técnica, mas no domínio é erro operacional. Resolvido: estudante deve ter no máximo um Polo por Disciplina; se houver mais de um, a interface usa o primeiro Polo encontrado.
