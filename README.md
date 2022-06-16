# discount_bitrix

# пример использования


use Bitrix\Main\Mail\Event;
use Bitrix\Sale;
Bitrix\Main\Loader::includeModule("catalog");

AddEventHandler('sale', 'OnSaleBasketItemRefreshData',Array("QSdiscount", 'BeforeBasketAddHandler')); // пересчёт корзины
AddEventHandler("main", "OnBeforeUserUpdate", Array("QSdiscount",'OnBeforeUserUpdateHandler'));
AddEventHandler("main", "OnAfterUserAuthorize", Array("QSdiscount", "updatePersonalDiscount"));
