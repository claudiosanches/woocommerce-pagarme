=== WooCommerce Pagar.me ===
Contributors: pagarme, claudiosanches
Tags: woocommerce, pagarme, payment
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 2.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Receba pagamentos por cartão de crédito e boleto bancário utilizando o Pagar.me

== Description ==

O [Pagar.me](https://pagar.me/) é a melhor forma de receber pagamentos online por cartão de crédito e boleto bancário, sendo possível o cliente fazer todo o pagamento sem sair da sua loja WooCommerce.

Saiba mais como o Pagar.me funciona:

[vimeo http://vimeo.com/74335951]

= Compatibilidade =

Compatível com desde a versão 2.2.x até 2.6.x do WooCommerce.

Este plugin funciona integrado com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar documentos do cliente como "CPF" ou "CNPJ", além dos campos "número" e "bairro" do endereço. Caso você queira remover todos os campos adicionais de endereço para vender Digital Goods, é possível utilizar o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/).

= Instalação =

Confira o nosso guia de instalação e configuração do Pagar.me na aba [Installation](http://wordpress.org/plugins/woocommerce-pagarme/installation/).

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/plugins/woocommerce-pagarme/faq/).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/woocommerce-pagarme).
* Criando um tópico no [fórum do Github](https://github.com/claudiosmweb/woocommerce-pagarme/issues).

= Colaborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-pagarme).

== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.

= Requerimentos: =

É necessário possuir uma conta no [Pagar.me](https://pagar.me/) e ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/).

= Configurações do Plugin: =

Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Finalizar compra" e configure as opção "Pagar.me - Boleto bancário" e "Pagar.me - Cartão de crédito".

Habilite a opção que você deseja, preencha as opções de **Chave de API** e **Chave de Criptografia** que você pode encontrar dentro da sua conta no Pagar.me em **API Keys**.

Também será necessário utilizar o plugin [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) para poder enviar campos de CPF e CNPJ.

Pronto, sua loja já pode receber pagamentos pelo Pagar.me.

== Frequently Asked Questions ==

= Qual é a licença do plugin? =

Este plugin esta licenciado como GPL.

= O que eu preciso para utilizar este plugin? =

* Ter instalado o plugin WooCommerce 2.2 ou superior.
* Possuir uma conta no [Pagar.me](https://pagar.me/).
* Pegar sua **Chave de API** e **Chave de Criptografia** no Pagar.me.
* Desativar a opção **Manter Estoque (minutos)** do WooCommerce.

= Quanto custa o Pagar.me? =

Confira os preços em "[Pagar.me - Preços](https://pagar.me/precos/)".

= Funciona com o Checkout Pagar.me? =

Sim, funciona desde a versão 2.0.0 para pagamentos com cartão de crédito.

= É possível utilizar a opção de pagamento recorrente? =

No momento ainda não é possível, entretanto iremos fazer esta integração em breve.

= O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto esta certo ? =

Sim, esta certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

= É obrigatório enviar todos os campos para processar o pagamento? =

Não é obrigatório caso você não utilize antifraude, no caso para digital goods.

É possível remover os campos de endereço, empresa e telefone, mantendo apenas nome, sobrenome e e-mail utilizando o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/).

= Problemas com a integração? =

Primeiro de tudo ative a opção **Log de depuração** e tente realizar o pagamento novamente.  
Feito isso copie o conteúdo do log e salve usando o [pastebin.com](http://pastebin.com) ou o [gist.github.com](http://gist.github.com), depois basta abrir um tópico de suporte [aqui](http://wordpress.org/support/plugin/woocommerce-pagarme).

= Mais dúvidas relacionadas ao funcionamento do plugin? =

Entre em contato [clicando aqui](http://wordpress.org/support/plugin/woocommerce-pagarme).

== Screenshots ==

1. Exemplo de checkout com cartão de crédito e boleto bancário do Pagar.me no tema Storefront.
2. Exemplo do Checkout Pagar.me para cartão de crédito.
3. Configurações para boleto bancário.
4. Configurações para cartão de crédito.

== Changelog ==

= 2.0.3 - 2016/06/02 =

* Corrigido erro ao fazer uma transação com o Checkout Pagar.me onde é adicionada taxa de juros.
* Adicionado campo informando o total pago pelo cliente incluindo juros quando aplicável.

= 2.0.2 - 2016/05/11 =

* Corrigida a validação de campos da finalização para o Checkout Pagar.me.
* Melhorada das mensagens de erro para quando não abrir o Checkout Pagar.me.

= 2.0.1 - 2016/04/04 =

* Permitida a validação dos campos da finalização antes de abrir o Checkout Pagar.me.
* Corrigida a mudança de status do Checkout Pagar.me.

= 2.0.0 - 2016/04/02 =

* Adicionado novo método para pagamento com cartões de crédito.
* Adicionado novo método para pagamentos com boleto bancário.
* Adicionado suporte ao Checkout Pagar.me para pagamentos com cartão de crédito.
* Corrigida a exibição do boleto na página "Minha conta", fazendo os boletos aparecer apenas quando o pedido esta com os status de pendente ou aguardando.

= 1.2.4 - 2016/02/04 =

* Adiciona opção para cobrar juros de todas as parcelas do cartão de crédito.

= 1.2.3 - 2016/01/27 =

* Removida dependência do plugin WooCommerce Extra Checkout Fields From Brazil.
* Removida dependência dos campos de endereço, telefone e empresa (obrigatório apenas nome, sobrenome e e-mail).
* Adicionado link para segunda via do boleto na tela de administração de pedidos e na página "Minha Conta".

= 1.2.2 - 2014/10/27 =

* Atualizada URL da biblioteca JavaScript do Pagar.me.

= 1.2.1 - 2014/10/27 =

* Corrigido o método que manipula os retornos do Pagar.me.

= 1.2.0 - 2014/10/12 =

* Adicionada opção para controlar o número de parcelas sem juros.

= 1.1.0 - 2014/09/07 =

* Adicionado suporte para a API de parcelas do Pagar.me.
* Adicionada opção de taxa de juros para as parcelas.
* Adicionado suporte para o WooCommerce 2.2.

= 1.0.0 =

* Versão incial do plugin.

== Upgrade Notice ==

= 2.0.3 =

* Corrigido erro ao fazer uma transação com o Checkout Pagar.me onde é adicionada taxa de juros.
* Adicionado campo informando o total pago pelo cliente incluindo juros quando aplicável.
