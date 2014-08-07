{capture name=path}{l s='Check payment' mod='payneteasy'}{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='payneteasy'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $is_empty}
	<p class="warning">{l s='Your shopping cart is empty.' mod='payneteasy'}</p>
{else}

    <div class="box cheque-box">
        <h3 class="page-subheading">
            {l s='Check payment' mod='payneteasy'}
        </h3>
        <p class="cheque-indent">
            <strong class="dark">
                {l s='You have chosen to pay by PaynetEasy.' mod='payneteasy'}
                {l s='Here is a short summary of your order:' mod='payneteasy'}
            </strong>
        </p>
        <p style="margin-top:20px;">
            - {l s='The total amount of your order comes to:' mod='payneteasy'}
            <span id="amount" class="price">{displayPrice price=$order_total}</span>
            {if $use_taxes == 1}
                {l s='(tax incl.)' mod='payneteasy'}
            {/if}
            <br />
            - {l s='PaynetEasy payment form will be displayed on the next page.' mod='payneteasy'}
            <br />
            - {l s='Please confirm your order by clicking "I confirm my order."' mod='payneteasy'}.
        </p>
    </div>

	<p class="cart_navigation exclusive">
		<a
            href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}"
            class="button-exclusive btn btn-default"
        >
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='payneteasy'}
        </a>
		<a
            href="{$link->getModuleLink('payneteasy', 'startsale', [], true)|escape:'html'}"
            class="button btn btn-default button-medium"
        >
            <span>{l s='I confirm my order' mod='payneteasy'}<i class="icon-chevron-right right"></i></span>
        </a>
	</p>
{/if}