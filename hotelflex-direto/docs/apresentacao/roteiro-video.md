# Roteiro do vídeo de apresentação · HotelFlex Direto

Base para gravar o vídeo de venda do sistema (cerca de 8 minutos e meio). A narração é a mesma das notas do apresentador dos slides. As telas estão em `docs/apresentacao/telas/`.

## Antes de gravar

1. `php bin/setup-database.php` (dados de demonstração limpos) e `php bin/send-notifications.php` (emite as notas da fila).
2. Navegador em 1920×1080, zoom 100%, sem barra de favoritos; **Ctrl+F5** em cada tela.
3. Contas (senha `Demo@1234`): hóspede `ana@hotelflex.test`, recepção `recepcao.centro@hotelflex.test`, administrador `admin@hotelflex.test`.
4. Para a NFC-e aparecer na lista, registre uma venda de café na hora durante o horário do café (06h às 10h), com um CPF válido, e emita a fila.
5. Sempre que aparecer nota fiscal, deixe visível a marca **SIMULAÇÃO · sem valor fiscal**.

## Cenas

### 1. HotelFlex Direto · 0:00

- **Na tela:** Abertura com o logo e a tela do celular do hóspede.
- **Gravar no sistema:** Login → `ana@hotelflex.test`, abrir "Minhas reservas" no celular (390 px).
- **Imagem de apoio:** celular.png

> Este é o HotelFlex Direto: uma plataforma para o hotel vender direto ao hóspede, receber o pagamento direto na própria conta, via Pix, e emitir a nota fiscal no momento certo, sem trabalho manual. Nos próximos minutos, vou mostrar como ele funciona para o hóspede, para a recepção e para o financeiro, e por que ele é diferente das alternativas que o hotel usa hoje.

### 2. O que trava o hotel independente hoje · 0:25

- **Na tela:** Texto animado com as três dores.
- **Gravar no sistema:** Sem gravação de tela: usar o slide.
- **Imagem de apoio:** —

> Antes de mostrar o sistema, vale olhar o dia a dia de um hotel independente. Primeiro, cada reserva que chega por um intermediário leva junto uma comissão e, muitas vezes, o contato do hóspede. Segundo, a operação fica espalhada: a reserva chega por um canal, o pagamento por outro, o comprovante por e-mail, e alguém na recepção junta tudo numa planilha. E terceiro, a nota fiscal acaba ficando para o fim, com dados faltando e cálculos manuais, justamente quando a reforma tributária começa a mudar as regras.

### 3. Um sistema, três ganhos · 0:55

- **Na tela:** Os três pilares sobre fundo verde.
- **Gravar no sistema:** Sem gravação de tela: usar o slide.
- **Imagem de apoio:** —

> A proposta do HotelFlex Direto se resume em três ganhos. Reserva direta: o hóspede reserva com o hotel, sem comissão por reserva. Pagamento direto: o Pix cai na chave do próprio hotel, sem intermediário segurando o dinheiro, e a recepção confirma em um clique. E fiscal automático: os dados para a nota são pedidos antes do pagamento, a nota sai no momento certo e os tributos já vêm calculados. Agora vamos ver isso na prática, começando pelo hóspede.

### 4. Buscar e escolher em segundos · 1:20

- **Na tela:** Busca de quartos.
- **Gravar no sistema:** Ana → Nova reserva → escolher entrada e saída → filtro "Menor preço" → Buscar quartos.
- **Imagem de apoio:** busca.png

> Para o hóspede, tudo começa na busca. Ele escolhe entrada e saída no calendário, e o sistema já mostra quantas diárias são. Pode filtrar por cidade, categoria e valor máximo da diária, e ordenar por preço. Cada cartão de quarto mostra o total, o valor da hora extra e até quando o cancelamento é grátis. Sem surpresa no fim da compra.

### 5. Revisar e pagar, com desconto no Pix · 1:45

- **Na tela:** Revisão com formas de pagamento e nota fiscal.
- **Gravar no sistema:** Clicar "Ver e reservar" → passar o mouse pelas 3 formas de pagamento → mostrar "Nota fiscal em nome de".
- **Imagem de apoio:** revisao.png

> Escolhido o quarto, o hóspede vai para a revisão. Aqui ele vê três formas de pagamento, todas direto ao hotel: Pix à vista com cinco por cento de desconto, metade agora e metade até vinte e quatro horas antes com dois por cento, ou um sinal e o restante no balcão. O desconto incentiva o pagamento antecipado, que protege o hotel contra o no-show. Na mesma tela ele escolhe em nome de quem sai a nota fiscal e aceita a política de cancelamento. Tudo explícito, antes de confirmar.

### 6. Horário flexível que vira receita · 2:15

- **Na tela:** Reserva confirmada com os controles de horário.
- **Gravar no sistema:** Abrir a reserva HF-1001 → arrastar "Sair mais tarde" e mostrar o valor mudando.
- **Imagem de apoio:** reserva-detalhe.png

> Depois que o pagamento é confirmado, o hóspede pode pedir para entrar mais cedo ou sair mais tarde. Ele arrasta o controle e vê na hora quanto custa. O sistema só oferece horários que cabem: respeita a tolerância grátis e o tempo de limpeza, então nunca invade a reserva do próximo hóspede. Quando o hotel aprova, a cobrança extra é criada sozinha. Aquele horário flexível que antes era favor vira receita.

### 7. O dia inteiro em uma tela · 2:45

- **Na tela:** Painel do dia da recepção.
- **Gravar no sistema:** Login → `recepcao.centro@hotelflex.test` → Painel do dia → filtros Chegadas/Limpeza.
- **Imagem de apoio:** painel.png

> Agora, o lado do hotel. A recepção trabalha numa tela só: o painel do dia. Cada quarto aparece com o status, se tem chegada, se está em limpeza ou ocupado, quem é o hóspede e se ainda há saldo a pagar. Se um quarto fica pronto antes do horário, um clique libera e avisa o hóspede pelo WhatsApp. Check-in e check-out também são um clique, e no check-out o quarto vai direto para a limpeza e a nota fiscal já entra na fila de emissão.

### 8. Pagamento direto, conferido pelo hotel · 3:15

- **Na tela:** Comprovantes para conferir e cobranças em aberto.
- **Gravar no sistema:** Pagamentos → "Baixar comprovante" → "Aprovar pagamento".
- **Imagem de apoio:** pagamentos.png

> No financeiro, o pagamento é direto: o hóspede paga na chave Pix do próprio hotel e envia o comprovante. O sistema confere o tipo do arquivo pelo conteúdo, guarda em local protegido, e a recepção aprova ou recusa informando o motivo. Pagamentos no balcão, em dinheiro, cartão ou Pix, também ficam registrados, com quem recebeu, quando e como. E se o prazo do Pix vence sem comprovante, a reserva expira e o quarto volta a ficar livre, sem ninguém precisar lembrar.

### 9. Hóspede sempre informado, sem trabalho manual · 3:45

- **Na tela:** Mensagens automáticas.
- **Gravar no sistema:** Mostrar "Mensagens enviadas" na reserva da Ana e a pasta `storage/outbox`.
- **Imagem de apoio:** —

> Cada etapa gera mensagens automáticas, por e-mail e por WhatsApp: reserva criada, pagamento confirmado ou recusado, quarto liberado, hora extra, cancelamento e o agradecimento no check-out. Essas mensagens passam por uma fila com novas tentativas, então, se o provedor cair, nada se perde e nada é enviado em dobro. O WhatsApp automático respeita a LGPD: só vai para quem autorizou, com a data registrada. E, quando precisa conversar, um botão abre o WhatsApp direto, já com o código da reserva.

### 10. Café da manhã vira produto · 4:15

- **Na tela:** Café da manhã: cliente e hotel.
- **Gravar no sistema:** Ana → Café da manhã → hotel, data, pessoas → opções de pagamento. Recepção → Café da manhã.
- **Imagem de apoio:** cafe-cliente.png, cafe-hotel.png

> O café da manhã deixa de ser só um custo e vira produto. Qualquer cliente cadastrado, hospedado ou não, pode reservar o café de um hotel, pagando via Pix antecipado com desconto ou no próprio hotel. A lotação é controlada: os hóspedes têm prioridade e o limite diário nunca é ultrapassado, nem com várias pessoas reservando ao mesmo tempo. E quem chega sem reserva compra na hora, com preço de balcão, já com CPF na nota.

### 11. Dados da nota antes de qualquer pagamento · 4:45

- **Na tela:** Dados para nota fiscal e consulta de CNPJ.
- **Gravar no sistema:** Ana → Dados para nota fiscal → digitar `12.ABC.345/01DE-35` → Consultar CNPJ.
- **Imagem de apoio:** faturamento.png

> Chegamos ao módulo fiscal, que é um grande diferencial. Primeiro: o sistema não deixa finalizar uma reserva sem CPF ou CNPJ, porque o hotel é obrigado a emitir a nota. O hóspede escolhe em nome de quem ela sai: dele, de outra pessoa ou de uma empresa. Para empresa, um clique consulta o CNPJ na Receita, preenche razão social e endereço e bloqueia empresas baixadas ou inaptas. O sistema já aceita o novo CNPJ com letras, que começou a ser emitido em julho de 2026, e protege os dados pessoais, mostrando o CPF mascarado.

### 12. A nota sai sozinha, no momento certo · 5:15

- **Na tela:** Lista de notas e pré-controle.
- **Gravar no sistema:** Recepção → Notas fiscais → "Emitir notas da fila" → mostrar o pré-controle.
- **Imagem de apoio:** notas.png

> A emissão acontece sozinha, no momento certo. A nota de serviço, a NFS-e, sai no check-out, com o valor final da estadia: diárias, horas extras e eventuais créditos. A nota do café, a NFC-e, sai quando o café é servido. A numeração é sequencial, sem buracos e sem repetição, mesmo com vários processos emitindo ao mesmo tempo. E se o emissor ficar fora do ar, o sistema tenta de novo e consulta antes de reenviar, então uma nota nunca sai em dobro. Cancelamentos exigem motivo e estornam os tributos.

### 13. PDF no padrão oficial, com QR code · 5:45

- **Na tela:** PDFs da DANFSe e da DANFE NFC-e.
- **Gravar no sistema:** Clicar "PDF" numa NFS-e e numa NFC-e; dar zoom na faixa de simulação e no QR code.
- **Imagem de apoio:** danfse.png, nfce.png

> Cada nota autorizada gera o PDF no padrão oficial: o DANFSe, em A4, no layout da NFS-e Nacional, para a hospedagem, e o DANFE NFC-e, em cupom de oitenta milímetros, para o café. Os dois trazem QR code, chave de acesso e o valor aproximado dos tributos. O hóspede baixa o dele na própria reserva, e a recepção baixa todos na lista de notas. Nesta primeira fase a emissão é simulada, e cada página traz a marca de documento sem valor fiscal.

### 14. Preparado para a reforma tributária · 6:15

- **Na tela:** Reforma tributária.
- **Gravar no sistema:** Sem gravação de tela: usar o slide.
- **Imagem de apoio:** —

> A reforma tributária muda as regras ano a ano até 2033, e o sistema foi feito para acompanhar isso. As alíquotas de CBS e IBS ficam numa tabela com data de vigência: quando a lei muda, entra uma linha nova, sem reprogramar nada. Em 2026, ano de teste, os dois tributos já aparecem na nota como informativos, e a regra que impede a empresa hóspede de tomar crédito na hotelaria já está tratada. Todo mês, o hotel e o contador veem um pré-controle: quanto de ISS o hotel recolhe, quanto foi retido por empresas, e o que é só informativo. É um apoio ao controle financeiro, que não substitui a apuração oficial.

### 15. Testado como sistema de produção · 6:45

- **Na tela:** Números de confiabilidade.
- **Gravar no sistema:** Opcional: terminal rodando `composer test:integration` com os ✔.
- **Imagem de apoio:** —

> Um sistema que mexe com dinheiro e com nota fiscal precisa ser confiável. São cento e quarenta e dois testes automáticos que conferem regras de preço, pagamentos, prazos, permissões e a emissão das notas. Testamos situações reais de pressão: oito hóspedes tentando o mesmo quarto no mesmo instante resultam em uma única reserva. Com oitenta e quatro mil reservas na base, as telas principais respondem abaixo de cento e sessenta milissegundos. E a segurança vem de fábrica: senhas criptografadas, proteção contra ataques de formulário, cada recepção vendo apenas o próprio hotel e credenciais fora do código.

### 16. Por que o HotelFlex Direto · 7:15

- **Na tela:** Tabela comparativa.
- **Gravar no sistema:** Sem gravação de tela: usar o slide.
- **Imagem de apoio:** —

> Comparando com as alternativas mais comuns. As agências online trazem visibilidade, mas cobram comissão em cada reserva e ficam entre o hotel e o hóspede. A combinação de planilha e WhatsApp não tem comissão, mas depende de trabalho manual em tudo: conferir pagamento, cobrar hora extra, avisar o hóspede e emitir a nota. O HotelFlex Direto junta o melhor dos dois: venda direta, sem comissão, com pagamento conferido, mensagens automáticas e a nota fiscal calculada e emitida pelo próprio sistema. Ele pode, inclusive, conviver com as agências, como o canal direto do hotel.

### 17. Próximos passos do produto · 7:45

- **Na tela:** Fases do produto.
- **Gravar no sistema:** Sem gravação de tela: usar o slide.
- **Imagem de apoio:** —

> O produto evolui em fases. Hoje, na fase um, tudo o que vimos está pronto para demonstrar, com a emissão de notas em modo de simulação. Na fase dois, a emissão passa a ser real, pela NFS-e Nacional ou por um intermediador, com o certificado digital do hotel, além do controle de notas de compra e créditos e de um acesso próprio para o contador. Na fase três, o sistema ganha um emissor alternativo para quando o principal falhar, o controle de pagamentos antecipados por mês de competência e a conciliação com a apuração assistida da Receita.

### 18. Mais receita direta, menos trabalho manual e o fiscal em dia. · 8:10

- **Na tela:** Encerramento e convite.
- **Gravar no sistema:** Logo e contato. Fim em 8:30.
- **Imagem de apoio:** —

> Para fechar: o HotelFlex Direto traz mais receita direta, porque o hotel vende sem comissão e cobra pelos horários flexíveis; menos trabalho manual, porque pagamentos, mensagens e notas acontecem sozinhos; e o fiscal em dia, com os dados certos desde a reserva e pronto para a reforma tributária. O próximo passo é simples: uma demonstração com os quartos e as regras do seu hotel. Obrigado.

## Observações

- Números citados (142 testes, 8 → 1, 160 ms com 84 mil reservas) vêm dos testes automáticos e do experimento EXP-003; atualize se mudarem.
- A emissão de notas é simulação nesta fase: nunca apresente os PDFs como documentos fiscais válidos.
- Preencha o contato do slide final antes de exportar: `[seu nome] · [e-mail] · [WhatsApp]`.
