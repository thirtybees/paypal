{*
 * 2017 Thirty Bees
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<div id="paypal-wrapper">

	{* PayPal configuration page header *}
	<div class="box half left">
		{if isset($PayPal_logo.LocalPayPalLogoLarge)}
			<img src="{$PayPal_logo.LocalPayPalLogoLarge|escape:'htmlall':'UTF-8'}" alt="" style="margin-bottom: -5px"/>
		{/if}
		<p id="paypal-slogan"><span class="dark">{l s='Leader in' mod='paypal'}</span> <span class="light">{l s='online payments' mod='paypal'}</span></p>
		<p>{l s='Easy, secure, fast payments for your buyers.' mod='paypal'}</p>
	</div>

	<div class="box half right">
		<ul class="tick">
			<li><span class="paypal-bold">{l s='Get more buyers' mod='paypal'}</span><br/>{l s='100 million-plus PayPal accounts worldwide' mod='paypal'}</li>
			<li><span class="paypal-bold">{l s='Access international buyers' mod='paypal'}</span><br/>{l s='190 countries, 25 currencies' mod='paypal'}</li>
			<li><span class="paypal-bold">{l s='Reassure your buyers' mod='paypal'}</span><br/>{l s='Buyers don\'t need to share their private data' mod='paypal'}</li>
			<li><span class="paypal-bold">{l s='Accept all major payment method' mod='paypal'}</span></li>
		</ul>
	</div>

	<div class="paypal-clear"></div>

	{*
	<!-- div class="bootstrap">
		<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" id="paypal_configuration">
			{if $PayPal_tls_verificator == '1'}
				<div style="margin-bottom: 20px;">
					{l s='Your configuration use version 1.2 of protocol TLS' mod='paypal'}<br/>
					<a href="{l s='https://www.paypal-knowledge.com/infocenter/index?page=content&widgetview=true&id=FAQ1914&viewlocale=en_US' mod='paypal'}">{l s='Click here to know more' mod='paypal'}</a>
				</div>
			{elseif $PayPal_tls_verificator == '0'}
				<div style="margin-bottom: 20px;">
					{l s='Your configuration use version 1.0 to communicate with PayPal.From July, all payments will be blocked.Thank you to approach your hosting company to enable the TLS version 1.2' mod='paypal'}<br/>
					<a href="{l s='https://www.paypal-knowledge.com/infocenter/index?page=content&widgetview=true&id=FAQ1914&viewlocale=en_US' mod='paypal'}">{l s='Click here to know more' mod='paypal'}</a>
				</div>
			{else}
			{/if}
			<button name="submitTlsVerificator" id="submitTlsVerificator">{l s='check your tls version' mod='paypal'}</button>

		</form>



	</div -->
	*}
	{if $PayPal_allowed_methods}
	{if $default_lang_iso == 'fr'}
		<div class="paypal-clear"></div>
		<hr/>
		<div class="box">
			{l s='Download the ' mod='paypal'}<a href="http://altfarm.mediaplex.com/ad/ck/3484-197941-8030-54"> {l s='Paypal Integration Guide' mod='paypal'}</a> {l s='on Thirty Bees and follow the configuration step by step' mod='paypal'}

		</div>
	{else}
		<div class="paypal-clear"></div>
		<hr/>
		<div class="box">
			{l s='Download the ' mod='paypal'}<a href="http://altfarm.mediaplex.com/ad/ck/3484-197941-8030-169"> {l s='Paypal Integration Guide' mod='paypal'}</a> {l s='on Thirty Bees and follow the configuration step by step' mod='paypal'}

		</div>
	{/if}
	<div class="paypal-clear"></div>
	<hr>

	<form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" id="paypal_configuration">
		{* PayPal configuration blocks *}
		<div class="box">
			<div style="line-height: 18px;">{l s='Getting started with PayPal only takes 5 minutes' mod='paypal'}</div>
			<div style="line-height: 20px; margin-top: 8px">
				<div>
					<label>{l s='Your country' mod='paypal'} :
						{$PayPal_country|escape:'htmlall':'UTF-8'}&nbsp;&nbsp;&nbsp;<a href="#" id="paypal_country_change" class="small">{l s='change' mod='paypal'}</a>
					</label>

					<div class="paypal-hide" id="paypal-country-form-content">
						<h3>{l s='Select your country' mod='paypal'} :</h3>

						<select name="paypal_country_default" id="paypal_country_default">
							{foreach from=$Countries item=country}
								<option value="{$country.id_country|escape:'htmlall':'UTF-8'}" {if $country.id_country == $PayPal_country_id}selected="selected"{/if}>{$country.name|escape:'htmlall':'UTF-8'}</option>
							{/foreach}
						</select>

						<br/>
						<br/>
					</div>
				</div>

				<label>{l s='Do you already have a PayPal business account' mod='paypal'} ?</label>
				<input type="radio" name="{PayPal::BUSINESS}" id="{PayPal::BUSINESS}_no" value="0" {if ${PayPal::BUSINESS} == 0}checked="checked"{/if} /> <label for="{PayPal::BUSINESS}_no">{l s='No' mod='paypal'}</label>
				<input type="radio" name="{PayPal::BUSINESS}" id="{PayPal::BUSINESS}_yes" value="1" style="margin-left: 14px" {if ${PayPal::BUSINESS} == 1}checked="checked"{/if} /> <label for="{PayPal::BUSINESS}_yes">{l s='Yes' mod='paypal'}</label>
			</div>
		</div>

		<div class="paypal-clear"></div>
		<hr/>

		{* SELECT YOUR SOLUTION *}
		<div class="box">

			<div class="box right half" id="paypal-call-button">
				<div id="paypal-call" class="box right"><span style="font-weight: bold">{l s='Need help ?' mod='paypal'}</span> {l s='Give us a call : xxxx' mod='paypal'} <img src="../modules/paypal/views/img/call.png" width="14px" alt="Phone"/></div>
				<div id="paypal-call-foonote" class="box right paypal-clear">{l s=' ' mod='paypal'}</div>
			</div>

			<span class="paypal-section">1</span>
			<h3 class="inline">{l s='Select your solution' mod='paypal'}</h3> <a href="{l s='https://altfarm.mediaplex.com/ad/ck/3484-148727-12439-23?ID=1' mod='paypal'}" target="_blank">{l s='Learn more' mod='paypal'}</a>

			<br/><br/><br/>

			{if (in_array($PayPal_WPS, $PayPal_allowed_methods))}
				<h4 class="inline">{l s='Need PayPal to process all your card payments ?' mod='paypal'}</h4>
				<img src="{$PayPal_logo.PayPalHorizontalSolutionPP|escape:'htmlall':'UTF-8'}" height="22px"/>
				<div class="paypal-clear"></div>
				<div class="form-block">
					{if (in_array($PayPal_WPS, $PayPal_allowed_methods))}
						{* WEBSITE PAYMENT STANDARD *}
						<label for="paypal_payment_wps">
							<input type="radio" name="{PayPal::PAYMENT_METHOD}" id="paypal_payment_wps" value='{$PayPal_WPS|escape:'htmlall':'UTF-8'}' {if ${PayPal::PAYMENT_METHOD} == $PayPal_WPS}checked="checked"{/if} />
							{l s='Choose' mod='paypal'} {l s='Website Payments Standard' mod='paypal'}
							<br/>
							<span class="description">{l s='Start accepting payments immediately.' mod='paypal'}<br/>{l s='No subscription fees, pay only when you get paid.' mod='paypal'}</span>
						</label>
					{/if}
					<div class="paypal-clear"></div>
					{if (in_array($PayPal_PPP, $PayPal_allowed_methods))}
						{* WEBSITE PAYMENT PLUS *}
						<br/>
						<label for="paypal_payment_ppp">
							<input type="radio" name="{PayPal::PAYMENT_METHOD}" id="paypal_payment_ppp" value='{$PayPal_PPP|escape:'htmlall':'UTF-8'}' {if ${PayPal::PAYMENT_METHOD} == $PayPal_PPP}checked="checked"{/if} />
							{l s='Choose' mod='paypal'} {l s='PayPal Plus' mod='paypal'}<br/>
							<span class="description"></span>
							<p class="toolbox"></p>
						</label>
					{/if}
				</div>
			{/if}
			<div class="paypal-clear"></div>
			{if (in_array($PayPal_ECS, $PayPal_allowed_methods))}
				<h4 class="inline">{l s='Need PayPal in addition to your existing card processor ?' mod='paypal'}</h4>
				<img src="{$PayPal_logo.LocalPayPalMarkSmall|escape:'htmlall':'UTF-8'}"/>
				<div class="form-block">
					{* EXPRESS CHECKOUT SOLUTION *}
					<label for="paypal_payment_ecs">
						<input type="radio" name="{PayPal::PAYMENT_METHOD}" id="paypal_payment_ecs" value='{$PayPal_ECS|escape:'htmlall':'UTF-8'}' {if ${PayPal::PAYMENT_METHOD} == $PayPal_ECS}checked="checked"{/if} />
						{l s='Choose' mod='paypal'} {l s='Express Checkout' mod='paypal'}<br/>
						<span class="description">{l s='Boost your online sales by 30%*.' mod='paypal'}</span>
					</label>
				</div>
			{/if}

			<hr/>
		</div>
		{* END OF PAYPAL LOGIN SECTION *}

		{* SUBSCRIBE OR OPEN YOUR PAYPAL BUSINESS ACCOUNT *}
		<div class="box" id="account">

			<span class="paypal-section">2</span>
			<h3 class="inline">{l s='Apply or open your PayPal Business account' mod='paypal'}</h3>

			<br/><br/>

			<div id="signup">
				{* Use cases 1 - 3 *}
				<a href="{l s='https://altfarm.mediaplex.com/ad/ck/3484-148727-12439-23?ID=2' mod='paypal'}" target="_blank" class="paypal-button paypal-signup-button" id="paypal-signup-button-u1">{l s='Sign Up' mod='paypal'}</a>
				<a href="{l s='https://altfarm.mediaplex.com/ad/ck/3484-148727-12439-23?ID=4' mod='paypal'}" target="_blank" class="paypal-button paypal-signup-button" id="paypal-signup-button-u2">{l s='Subscribe' mod='paypal'}</a>
				<a href="{l s='https://altfarm.mediaplex.com/ad/ck/3484-148727-12439-23?ID=3' mod='paypal'}" target="_blank" class="paypal-button paypal-signup-button" id="paypal-signup-button-u3">{l s='Sign Up' mod='paypal'}</a>

				{* Use cases 4 - 6 *}
				<a href="{l s='https://altfarm.mediaplex.com/ad/ck/3484-148727-12439-23?ID=4' mod='paypal'}#" target="_blank" class="paypal-button paypal-signup-button" id="paypal-signup-button-u5">{l s='Subscribe' mod='paypal'}</a>

				<br/><br/>

				{* Use cases 1 - 3 *}
				<span class="paypal-signup-content" id="paypal-signup-content-u1">{l s='Once your account is created, come back to this page in order to complete step 3.' mod='paypal'}</span>
				<span class="paypal-signup-content" id="paypal-signup-content-u2">{l s='Click on the SAVE button only when PayPal has approved your subscription for this product, otherwise you won\'t be able to process payment. This process can take up to 3-5 days.' mod='paypal'}</span>
				<span class="paypal-signup-content" id="paypal-signup-content-u3">{l s='Once your account is created, come back to this page in order to complete step 3.' mod='paypal'}</span>

				{* Use cases 4 - 6 *}
				<span class="paypal-signup-content" id="paypal-signup-content-u5">{l s='Click on the SAVE button only when PayPal has approved your subscription for this product, otherwise you won\'t be able to process payment. This process can take up to 3-5 days.' mod='paypal'}<br/>
					{l s='If your application for Website Payments Pro has already been approved by PayPal, please go directly to step 3' mod='paypal'}.</span>

			</div>

			<hr/>

		</div>

		{* ENABLE YOUR ONLINE SHOP TO PROCESS PAYMENT *}
		<div class="box paypal-disabled" id="credentials">
			<span class="paypal-section">3</span>
			<h3 class="inline">{l s='Process payments on your online shop' mod='paypal'}</h3>
			<br/><br/>

			<div class="paypal-hide" id="configuration">
				{* Credentials *}

				<div id="standard-credentials">
					<h4>{l s='Communicate your PayPal identification info to Thirty Bees' mod='paypal'}</h4>

					<br/>

					<a href="#" class="paypal-button" id="paypal-get-identification">
						{l s='Get my PayPal identification info' mod='paypal'}<p class="toolbox">{l s='After clicking on the “Get my PayPal identification info” button, enter your login and password in the pop up, copy your PayPal identification info from the pop up and paste them is the below fields.' mod='paypal'}</p>
					</a>

					<br/><br/>

					<dl>
						<dt><label for="{PayPal::API_USER|escape:'html':'UTF-8'}">{l s='API username' mod='paypal'} : </label></dt>
						<dd><input type='text' name="{PayPal::API_USER|escape:'html':'UTF-8'}" id="{PayPal::API_USER|escape:'html':'UTF-8'}" value="{${PayPal::API_USER}}" autocomplete="off" size="85"/></dd>
						<dt><label for="{${PayPal::API_PASSWORD}|escape:'html':'UTF-8'}">{l s='API password' mod='paypal'} : </label></dt>
						<dd><input type='text' size="85" name="{PayPal::API_PASSWORD|escape:'html':'UTF-8'}" id="{PayPal::API_PASSWORD|escape:'html':'UTF-8'}" value="{${PayPal::API_PASSWORD}}" autocomplete="off"/></dd>
						<dt><label for="{PayPal::API_SIGNATURE|escape:'html':'UTF-8'}">{l s='API signature' mod='paypal'} : </label></dt>
						<dd><input type='text' size="85" name="{PayPal::API_SIGNATURE|escape:'html':'UTF-8'}" id="{PayPal::API_SIGNATURE|escape:'html':'UTF-8'}" value="{${PayPal::API_SIGNATURE}}" autocomplete="off"/></dd>
					</dl>
					<div class="paypal-clear"></div>
					<span class="description">{l s='Please check once more that you pasted all the characters.' mod='paypal'}</span>
				</div>

				<div id="paypalplus-credentials">
					<h4>{l s='Provide your PayPal API credentials to Thirty Bees' mod='paypal'}</h4>

					<br/>

					<dl>
						<dt><label for="{PayPal::CLIENT_ID}">{l s='Client ID' mod='paypal'} : </label></dt>
						<dd><input type='text' name="{PayPal::CLIENT_ID}" id="{PayPal::CLIENT_ID}" value="{${PayPal::CLIENT_ID}|escape:'html':'UTF-8'}" autocomplete="off" size="85"/></dd>
						<dt><label for="{PayPal::SECRET}">{l s='Secret' mod='paypal'} : </label></dt>
						<dd><input type='password' size="85" name="{PayPal::SECRET}" id="{PayPal::SECRET}" value="{$PayPal_plus_secret|escape:'html':'UTF-8'}" autocomplete="off"/></dd>
						<dt><label for="webprofile">{l s='Use personalization (use your logo and your shop name on Paypal) :' mod='paypal'}</label></dt>
						<dd>
							<input type="radio" name="{PayPal::WEB_PROFILE_ID}" value="1" id="{PayPal::WEB_PROFILE_ID}_yes" {if ${PayPal::WEB_PROFILE_ID}}checked="checked"{/if} /> <label for="{PayPal::WEB_PROFILE_ID}_yes">{l s='Yes' mod='paypal'}</label><br/>
							<input type="radio" name="{PayPal::WEB_PROFILE_ID}" value="0" id="{PayPal::WEB_PROFILE_ID}_no" {if ${PayPal::WEB_PROFILE_ID} == '0'}checked="checked"{/if} /> <label for="{PayPal::WEB_PROFILE_ID}_no">{l s='No' mod='paypal'}</label>
						</dd>
					</dl>
					<div class="paypal-clear"></div>
				</div>

				<div class="paypal-clear"></div>

				<h4>{l s='To finalize setting up your PayPal account, you need to' mod='paypal'} : </h4>
				<p><span class="paypal-bold">1.</span> {l s='Confirm your email address : check the email sent by PayPal when you created your account' mod='paypal'}</p>
				<p><span class="paypal-bold">2.</span> {l s='Link your PayPal account to a bank account or a credit card : log into your PayPal account and go to "My business setup"' mod='paypal'}</p>

				<h4>{l s='Configuration options' mod='paypal'}</h4>

				<div id="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}" class="paypal-hide">
					<p>{l s='Use express checkout shortcut' mod='paypal'}</p>
					<p class="description">{l s='Offer your customers a 2-click payment option' mod='paypal'}</p>
					<input type="radio" name="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}" id="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}_no_shortcut" value="1" {if ${PayPal::EXPRESS_CHECKOUT_SHORTCUT} == 1}checked="checked"{/if} /> <label for="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}_no_shortcut">{l s='Yes' mod='paypal'} (recommended)</label><br/>
					<input type="radio" name="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}" id="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}_shortcut" value="0" {if ${PayPal::EXPRESS_CHECKOUT_SHORTCUT} == 0}checked="checked"{/if} /> <label for="{PayPal::EXPRESS_CHECKOUT_SHORTCUT}_shortcut">{l s='No' mod='paypal'}</label>
				</div>

				<div id="{PayPal::IN_CONTEXT_CHECKOUT}" class="paypal-hide">
					<p>{l s='Use PayPal In Context Checkout' mod='paypal'}</p>
					<p class="description">{l s='Make your client pay without leaving your website' mod='paypal'}</p>
					<input type="radio" name="{PayPal::IN_CONTEXT_CHECKOUT}" id="{PayPal::IN_CONTEXT_CHECKOUT}_no_in_context_checkout" value="1" {if ${PayPal::IN_CONTEXT_CHECKOUT} == 1}checked="checked"{/if} /> <label for="{PayPal::IN_CONTEXT_CHECKOUT}_no_in_context_checkout">{l s='Yes' mod='paypal'}</label><br/>
					<input type="radio" name="{PayPal::IN_CONTEXT_CHECKOUT}" id="{PayPal::IN_CONTEXT_CHECKOUT}_in_context_checkout" value="0" {if ${PayPal::IN_CONTEXT_CHECKOUT} == 0}checked="checked"{/if} /> <label for="{PayPal::IN_CONTEXT_CHECKOUT}_in_context_checkout">{l s='No' mod='paypal'}</label>
				</div>

				<div>
					<p>{l s='Use the PayPal Login functionnality' mod='paypal'}{if $default_lang_iso == 'fr'}{l s='(*see the ' mod='paypal'}<a href="http://altfarm.mediaplex.com/ad/ck/3484-197941-8030-96"> {l s='integration guide' mod='paypal'} </a>{l s='and follow the steps' mod='paypal'}){else}{l s='(*see the ' mod='paypal'}<a href="http://altfarm.mediaplex.com/ad/ck/3484-197941-8030-170"> {l s='integration guide' mod='paypal'} </a>{l s='and follow the steps' mod='paypal'}){/if}</p>
					<p class="description">
						{l s='This function allows to your clients to connect with their PayPal credentials to shorten the check out' mod='paypal'}
					</p>
					<div id="{PayPal::LOGIN}_yes_or_no" class="">
						<p class="description"></p>
						<input type="radio" name="{PayPal::LOGIN}" id="{PayPal::LOGIN}_yes" value="1" {if ${PayPal::LOGIN} == 1}checked="checked"{/if} /> <label for="{PayPal::LOGIN}_yes">{l s='Yes' mod='paypal'} </label><br/>
						<input type="radio" name="{PayPal::LOGIN}" id="{PayPal::LOGIN}_no" value="0" {if ${PayPal::LOGIN} == 0}checked="checked"{/if} /> <label for="{PayPal::LOGIN}_no">{l s='No' mod='paypal'}</label>
					</div>
					<div id="{PayPal::LOGIN}_configuration"{if ${PayPal::LOGIN} == 0} style="display: none;"{/if}>
						<p>
							{l s='Fill in the informations of your PayPal account' mod='paypal'}.{if $default_lang_iso == 'fr'}(* {l s='See' mod='paypal'} <a href="http://altfarm.mediaplex.com/ad/ck/3484-197941-8030-96">{l s='Integration Guide' mod='paypal'}</a>){/if}.
						</p>
						<dl>
							<dt>
								{l s='Client ID' mod='paypal'}
							</dt>
							<dd>
								<input type="text" name="{PayPal::CLIENT_ID}" value="{${PayPal::CLIENT_ID}|escape:'htmlall':'UTF-8'}" autocomplete="off" size="85">
							</dd>
							<dt>
								{l s='Secret' mod='paypal'}
							</dt>
							<dd>
								<input type="text" name="{PayPal::SECRET}" value="{${PayPal::SECRET}|escape:'htmlall':'UTF-8'}" autocomplete="off" size="85">
							</dd>

							<dt>
								{l s='Choose your template' mod='paypal'}
							<p class="description" style="margin-top:-10px;">({l s='Translated in your language' mod='paypal'})</p>
							</dt>
							<dd>
								<input type="radio" name="{PayPal::LOGIN_TPL}" id="{PayPal::LOGIN_TPL}_blue" value="1"{if ${PayPal::LOGIN_TPL} == 1} checked{/if} />
								<label for="{PayPal::LOGIN_TPL}_blue">
									<img src="../modules/paypal/views/img/paypal_login_blue.png" alt="">
								</label>
								<br/>
								<input type="radio" name="{PayPal::LOGIN_TPL}" id="{PayPal::LOGIN_TPL}_neutral" value="2"{if ${PayPal::LOGIN_TPL} == 2} checked{/if} />
								<label for="{PayPal::LOGIN_TPL}_neutral">
									<img src="../modules/paypal/views/img/paypal_login_grey.png" alt="">
								</label>
							</dd>
						</dl>


						<div class="paypal-clear"></div>
					</div>
				</div>


				<p>{l s='Use Sand box' mod='paypal'}</p>
				<p class="description">{l s='Activate a test environment in your PayPal account (only if you are a developer).' mod='paypal'} <a href="{l s='https://developer.paypal.com/' mod='paypal'}" target="_blank">{l s='Learn more' mod='paypal'}</a></p>
				<input type="radio" name="{PayPal::SANDBOX}" id="{PayPal::SANDBOX}_live_mode" value="0" {if ${PayPal::SANDBOX} == 0}checked="checked"{/if} /> <label for="{PayPal::SANDBOX}_live_mode">{l s='Live mode' mod='paypal'}</label><br/>
				<input type="radio" name="{PayPal::SANDBOX}" id="{PayPal::SANDBOX}_test_mode" value="1" {if ${PayPal::SANDBOX} == 1}checked="checked"{/if} /> <label for="{PayPal::SANDBOX}_test_mode">{l s='Test mode' mod='paypal'}</label>

				<br/>

				<p>{l s='Payment type' mod='paypal'}</p>
				<p class="description">{l s='Choose your way of processing payments (automatically vs.manual authorization).' mod='paypal'}</p>
				<input type="radio" name="{PayPal::CAPTURE}" id="paypal_direct_sale" value="0" {if ${PayPal::CAPTURE} == 0}checked="checked"{/if} /> <label for="paypal_direct_sale">{l s='Direct sales (recommended)' mod='paypal'}</label><br/>
				<input type="radio" name="{PayPal::CAPTURE}" id="paypal_manual_capture" value="1" {if ${PayPal::CAPTURE} == 1}checked="checked"{/if} /> <label for="paypal_manual_capture">{l s='Authorization/Manual capture (payment shipping)' mod='paypal'}</label>

				<br/><br/>
			</div>

			<input type="hidden" name="submitPaypal" value="paypal_configuration"/>
			<input type="submit" name="submitButton" value="{l s='Save' mod='paypal'}" id="paypal_submit"/>

			<div class="box paypal-hide" id="paypal-test-mode-confirmation">
				<h3>{l s='Activating the test mode implies that' mod='paypal'} :</h3>
				<ul>
					<li>{l s='You won\'t be able to accept payment' mod='paypal'}</li>
					<li>{l s='You will need to come back to the PayPal module page in order to complete the Step 3 before going live.' mod='paypal'}</li>
					<li>{l s='You\'ll need to create an account on the PayPal sandbox site' mod='paypal'} (<a href="https://developer.paypal.com/" target="_blank">{l s='learn more' mod='paypal'}</a>)</li>
					<li>{l s='You\'ll need programming skills' mod='paypal'}</li>
				</ul>

				<h4>{l s='Are you sure you want to activate the test mode ?' mod='paypal'}</h4>

				<div id="buttons">
					<button class="fancy_confirm" name="fancy_confirm" value="0">{l s='No' mod='paypal'}</button>
					<button class="fancy_confirm" name="fancy_confirm" value="1">{l s='Yes' mod='paypal'}</button>
				</div>
			</div>

			{if isset($PayPal_save_success)}
				<div class="box paypal-hide" id="paypal-save-success">
					<h3>{l s='Congratulation !' mod='paypal'}</h3>
					{if ${PayPal::SANDBOX} == 0}
						<p>{l s='You can now start accepting Payment  with PayPal.' mod='paypal'}</p>
					{elseif ${PayPal::SANDBOX} == 1}
						<p>{l s='You can now start testing PayPal solutions. Don\'t forget to comeback to this page and activate the live mode in order to start accepting payements.' mod='paypal'}</p>
					{/if}
				</div>
			{/if}
			{if isset($PayPal_save_failure)}
				<div class="box paypal-hide" id="paypal-save-failure">
					<h3>{l s='Error !' mod='paypal'}</h3>
					<p>{l s='You need to complete the PayPal identification Information in step 3 otherwise you won\'t be able to accept payment.' mod='paypal'}</p>
				</div>
			{/if}

			<div class="box paypal-hide" id="js-paypal-save-failure">
				<h3>{l s='Error !' mod='paypal'}</h3>
				<p>{l s='You need to complete the PayPal identification Information in step 3 otherwise you won\'t be able to accept payment.' mod='paypal'}</p>
			</div>

			<hr/>
		</div>
	</form>

	<div class="box">
		<p class="description">
			{l s='*Source: Forrester research realized in march 2011' mod='paypal'}
		</p>
	</div>
	{else}
	<div class="paypal-clear"></div>
	<hr/>
	<div class="box">
		<p>{l s='Your country is not available for this module please go on Prestashop addons to see the different possibilities.' mod='paypal'}</p>
	</div>
	<hr/>
</div>

{/if}

</div>
