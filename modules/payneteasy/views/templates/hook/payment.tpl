<style>
p.payment_module a.payneteasy:after
{
    color: #777777;
    content: "";
    display: block;
    font-family: "FontAwesome";
    font-size: 25px;
    height: 22px;
    margin-top: -11px;
    position: absolute;
    right: 15px;
    top: 50%;
    width: 14px;
}

p.payment_module a.payneteasy
{
    background: url("/modules/payneteasy/checkout_logo.jpg") no-repeat scroll 15px 15px #fbfbfb;
}

p.payment_module a.payneteasy:hover
{
    background-color: #f6f6f6;
}
</style>

<div class="row">
	<div class="col-xs-12 col-md-6">
        <p class="payment_module">
			<a href="{$link->getModuleLink('payneteasy', 'confirmation', [], true)|escape:'html'}" class="payneteasy">
                PaynetEasy
			</a>
		</p>
    </div>
</div>
