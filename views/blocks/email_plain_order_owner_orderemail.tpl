[{$smarty.block.parent}]

[{if $order->isCustomPaymentMethod() }]

  [{if $order->isPaymentPending() }]
    [{oxmultilang ident="wd_payment_awaiting"}]
  [{elseif $order->isPaymentSuccess() }]
    [{oxmultilang ident="wd_payment_success_text"}]
  [{elseif $order->isPaymentFailed()}]
    [{oxmultilang ident="wd_order_error_info"}]
  [{else}]
    [{oxmultilang ident="wd_payment_awaiting"}]
  [{/if}]

[{/if}]
