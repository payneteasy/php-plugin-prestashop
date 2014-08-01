{capture name=path}
    <a href="{$link->getPageLink('order')|escape:'html'}">
        {l s='Your shopping cart'}
    </a>
    <span class="navigation-pipe"> {$navigationPipe|escape:'htmlall':'UTF-8'} </span> {l s='PaynetEasy' mod='payneteasy'}
{/capture}

<div class="error">
    {$error_message}
</div>