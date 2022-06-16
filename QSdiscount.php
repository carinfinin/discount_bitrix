<?
use Bitrix\Main\Mail\Event;
use Bitrix\Sale;
use \Bitrix\Main\Data\Cache;
Bitrix\Main\Loader::includeModule("catalog");

class QSdiscount {

    public $STATUS = [
        25 => 'Базовый - 0%',
        19 => 'Стандарт - 1%',
        20 => 'Серебренный - 2%',
        21 => 'Золотой - 3%',
        22 => 'Платинум - 4%',
        23 => 'Премиум - 5%',
        24 => 'VIP - 7%'
    ];

    public function getDiscountUsers($id) {
        $result = '';
        $rsUsers = CUser::GetList(($by="personal_country"), ($order="desc"), ['ID' => $id], ['SELECT' => array("UF_*")]);
        if($res = $rsUsers->NavNext(true, "f_")) {
            $result = $res['UF_DISCOUNT_PERSONAL'];
            return $this->STATUS[$result ? $result : 25];
        }
    }

    public function updatePersonalDiscount($arUser) {
        $ID = $arUser['user_fields']['ID'];
        $sum = 0;
        $discount = '';

        $associative = [
            0 => 25,
            50000 => 19,
            100000 => 20,
            200000 => 21,
            300000 => 22,
            500000 => 23,
            1000000 => 24
        ];
        global $DB;
        $arFilter = Array(
            "USER_ID" => $ID,
            "PAYED" => "Y",
            ">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), time() - 86400 * 90)
        );
        $db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
        while ($ar_sales = $db_sales->Fetch())
        {
            $sum = $sum + $ar_sales['SUM_PAID'];
        }
        foreach (array_keys($associative) as $val) {
            if($val <= $sum) {
                $discount = $val;
            }
        }
        $user = new CUser;
        $fields = Array(
            "UF_DISCOUNT_PERSONAL" => [$associative[$discount]],
        );
        $user->Update($ID, $fields);
    }

    public function OnBeforeUserUpdateHandler(&$arFields)
    {
        $id = 9; // юр. лица
        $arrGroupUser = CUser::GetUserGroup($arFields['ID']);

        foreach($arFields['GROUP_ID'] as $group) {
            if($group['GROUP_ID'] == $id && !in_array($id, $arrGroupUser)) {
                Event::send(array(
                    "EVENT_NAME" => "USER_UPDATE",
                    "LID" => "s1",
                    "C_FIELDS" => array(
                        "EMAIL" => $arFields['EMAIL'],
                        "ID" => $arFields['ID'],
                        "LOGIN" => $arFields['LOGIN'],
                        "LAST_NAME" => $arFields['LAST_NAME'],
                        "NAME" => $arFields['NAME'],
                    ),
                ));
                $arFields['UF_CHECK_ENTITY'] = 0;
            }
        }
    }
    public function BeforeBasketAddHandler($BasketItem)
    {
        $id = 0;
        global $USER;
        if ($USER->IsAuthorized()) {
            $id = $USER->GetId();
            $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
            $basketProducts = array_keys($basket->getListOfFormatText());
            if(in_array($BasketItem->getId(), $basketProducts)) {
                $arPropItemBasket = [
                    'QUANTITY' => $BasketItem->getQuantity(),
                    'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                    'LID' => 's1',
                    'PRODUCT_PROVIDER_CLASS' => '\Qs\Sale\CatalogProductProvider'
                ];
//            $BasketItem->setField('DISCOUNT_VALUE', 100);
                $BasketItem->setFields($arPropItemBasket);
                $BasketItem->save();
            }
        }
    }

    
}