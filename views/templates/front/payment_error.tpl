{capture name=path}
    <a href="{$link->getPageLink('order')|escape:'html'}">
        {l s='Your shopping cart'}
    </a>
    <span class="navigation-pipe"> {$navigationPipe|escape:'htmlall':'UTF-8'} </span> {l s='PaynetEasy' mod='payneteasy'}
{/capture}

<h1 class="page-heading">{l s='Error occured' mod='payneteasy'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<div class="alert alert-danger">
    {$error_message}
</div>

<p class="cart_navigation exclusive">
    <a
        href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}"
        class="button-exclusive btn btn-default"
    >
        <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='payneteasy'}
    </a>
</p>