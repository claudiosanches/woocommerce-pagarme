=== WooCommerce Pagar.me ===
Contributors: pagarme, claudiosanches
Tags: woocommerce, pagarme, payment
Requires at least: 4.0
Tested up to: 4.4
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Receba pagamentos por cartão de crédito e boleto bancário utilizando o Pagar.me

== Description ==

O [Pagar.me](https://pagar.me/) é a melhor forma de receber pagamentos online por cartão de crédito e boleto bancário, sendo possível o cliente fazer todo o pagamento sem sair da sua loja WooCommerce.

Saiba mais como o Pagar.em funciona:

[vimeo http://vimeo.com/74335951]

= Compatibilidade =

Compatível com as versões 2.2.x, 2.3.x, 2.4.x e 2.5.x do WooCommerce.

Este plugin funciona integrado com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar documentos do cliente como "CPF" ou "CNPJ", além dos campos "número" e "bairro" do endereço.

= Instalação =

Confira o nosso guia de instalação e configuração do Pagar.me na aba [Installation](http://wordpress.org/extend/plugins/woocommerce-pagarme/installation/).

= Dúvidas? =

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/extend/plugins/woocommerce-pagarme/faq/).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/woocommerce-pagarme).
* Criando um tópico no [fórum do Github](https://github.com/claudiosmweb/woocommerce-pagarme/issues).

= Coloborar =

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/claudiosmweb/woocommerce-pagarme).

== Installation ==

= Instalação do plugin: =

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.

= Requerimentos: =

É necessário possuir uma conta no [Pagar.me](https://pagar.me/) e ter instalado o [WooCommerce](http://wordpress.org/extend/plugins/woocommerce/).

= Configurações do Plugin: =

Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Finalizar compra" > "Pagar.me".

Habilite o Pagar.me, preencha as opções de **Chave de API** e **Chave de Criptografia** que você pode encontrar dentro da sua conta no Pagar.me em **
API Keys**.

= Configurações no WooCommerce =

No WooCommerce 2.2 ou superior existe uma opção para cancelar a compra e liberar o estoque depois de alguns minutos.

Esta opção não funciona muito bem com o Pagar.me, pois pagamentos por boleto bancário pode demorar até 48 horas para serem validados.

Para corrigir isso é necessário ir em "WooCommerce" > "Configurações" > "Produtos" > "Inventário" e limpar (deixe em branco) a opção **Manter Estoque (minutos)**.

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

= É possível utilizar a opção de pagamento recorrente? =

No momento ainda não é possível, entretanto iremos fazer esta integração em breve.

= Funciona com o Checkout Pagar.me? =

Ainda não, mas será no futuro.

= O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto esta certo ? =

Sim, esta certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

= É obrigatório enviar todos os campos para processar o pagamento? =

Não é obrigatório caso você não utilize anti-fraude, no caso para digital goods.

É possível remover os campos de endereço, empresa e telefone, mantendo apenas nome, sobrenome e e-mail utilizando [este plugin](https://gist.github.com/claudiosmweb/590bf8e9e5d9dd0ba104).

= Problemas com a integração? =

Primeiro de tudo ative a opção **Log de depuração** e tente realizar o pagamento novamente.  
Feito isso copie o conteúdo do log e salve usando o [pastebin.com](http://pastebin.com) ou o [gist.github.com](http://gist.github.com), depois basta abrir um tópico de suporte [aqui](http://wordpress.org/support/plugin/woocommerce-pagarme).

= Mais dúvidas relacionadas ao funcionamento do plugin? =

Entre em contato [clicando aqui](http://wordpress.org/support/plugin/woocommerce-pagarme).

== Screenshots ==

1. Configurações do plugin.
2. Plugin em funcionamento na página de finalizar pedido.

== Changelog ==

= 1.2.3 - 2015/01/27 =

* Removida dependência do plugin WooCommerce Extra Checkout Fields From Brazil.
* Removida dependência dos campis de endereço, telefone e empresa (obrigatório apenas nome, sobrenome e e-mail).
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

= 1.2.3 =

* Removida dependência do plugin WooCommerce Extra Checkout Fields From Brazil.
* Removida dependência dos campis de endereço, telefone e empresa (obrigatório apenas nome, sobrenome e e-mail).
* Adicionado link para segunda via do boleto na tela de administração de pedidos e na página "Minha Conta".
