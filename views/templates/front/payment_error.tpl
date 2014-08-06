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
    {if $is_guest}
        <a
            class="button-exclusive btn btn-default"
            href="{$link->getPageLink('guest-tracking', true, NULL, "id_order={$reference_order}&email={$email}")|escape:'html':'UTF-8'}"
            title="{l s='Follow my order'}"
        >
            <i class="icon-chevron-left"></i>{l s='Follow my order'}
        </a>
    {else}
        <a
            class="button-exclusive btn btn-default"
            href="{$link->getPageLink('history', true)|escape:'html':'UTF-8'}"
            title="{l s='Back to orders'}"
        >
            <i class="icon-chevron-left"></i>{l s='Back to orders'}
        </a>
    {/if}
</p>