[{*
* Shop System Plugins:
* - Terms of Use can be found under:
* https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
* - License can be found under:
* https://github.com/wirecard/oxid-ee/blob/master/LICENSE
*
*}]

[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{oxstyle include=$oViewConf->getPaymentGatewayUrl("out/css/wirecard_wdoxidee_common.css")}]
[{oxstyle include=$oViewConf->getPaymentGatewayUrl("out/css/wirecard_wdoxidee_table.css")}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
  [{$oViewConf->getHiddenSid()}]
  <input type="hidden" name="oxid" value="[{$oxid}]">
  <input type="hidden" name="cl" value="[{$controller}]">
[{if $actions|@count > 0}]



    [{if $message}]
    <div class="wdoxidee-messagebox wdoxidee-messagebox--[{$message.type}]">
      [{$message.message}]
    </div>
    [{/if}]

    [{if $data}]
      [{include file="table.tpl"}]
    [{include file="post_processing_buttons_row.tpl"}]
    [{else}]
    <table cellspacing="0" cellpadding="0" border="0" width="600">
      <tr>
        <td width="25%">[{oxmultilang ident="wd_amount"}] ([{$currency}])</td>
        <td><input type="text" name="amount" pattern="^[0-9]*[\.,]?[0-9]+$" value="[{$maxAmount}]"
                   size="25"></td>
      </tr>
    </table>
    [{include file="post_processing_buttons_row.tpl"}]
    [{/if}]
  [{else}]
  <div class="wdoxidee-messagebox wdoxidee-messagebox--info">
    [{$emptyText}]
  </div>
  [{/if}]
</form>

[{if $message.type === 'success'}]
  <script type="text/javascript">
    top.oxid.admin.updateList();
  </script>
  [{/if}]

[{if $oView->shouldDisplayLiveChat()}]
  [{include file="live_chat.tpl"}]
  [{/if}]

[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]
