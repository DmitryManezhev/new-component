<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// подключаем общий css
$this->addExternalCss($this->getFolder() . "/style.css");

$arItem = $arResult["ITEM"];
if (!$arItem) {
    echo '<div class="tariffs-empty">' . GetMessage("TARIFFS_NO_ITEMS") . '</div>';
    return;
}
?>
	<a href="/tariffs/" class="btn btn-action button-nav button-transparent">
               ← Назад
            </a>

<div class="tariff-card-detail" data-product-id="<?= $arItem['ID'] ?>">


    <?php if ($arItem['DETAIL_PICTURE']): ?>
        <div class="tariff-image">
            <img src="<?= $arItem['DETAIL_PICTURE'] ?>" alt="<?= htmlspecialchars($arItem['NAME']) ?>" />
        </div>
    <?php endif; ?>

    <div class="tariff-info">
        <h1 class="tariff-title"><?= htmlspecialchars($arItem['NAME']) ?></h1>

        <?php if ($arItem['DETAIL_TEXT']): ?>
            <p class="tariff-description"><?= $arItem['DETAIL_TEXT'] ?></p>
        <?php endif; ?>

        <ul class="tariff-features">
            <?php if (!empty($arItem['PROPERTIES']) && is_array($arItem['PROPERTIES'])): ?>
                <?php foreach ($arItem['PROPERTIES'] as $code => $prop): ?>
                    <?php if (!empty($prop['VALUE'])): ?>
                        <li>
    <strong><?= htmlspecialchars($prop['NAME']) ?>:</strong>
    <span>
        <?php
        if ($prop['PROPERTY_TYPE'] === 'L') {

            echo htmlspecialchars($prop['VALUE_ENUM']);

        } elseif ($prop['PROPERTY_TYPE'] === 'E') { 

            $el = CIBlockElement::GetByID($prop['VALUE'])->GetNext();
            echo htmlspecialchars($el['NAME']);

        } else {

            echo is_array($prop['VALUE']) ? implode(', ', $prop['VALUE']) : htmlspecialchars($prop['VALUE']);
        }
        ?>
    </span>
</li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Характеристики уточняйте у менеджера</li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="tariff-actions">
        <div class="tariff-price">
            <?php if ($arItem['PRICE'] > 0): ?>
                <span class="price-value"><?= $arItem['PRICE_FORMATTED'] ?></span>
                <span class="price-period"><?= GetMessage("TARIFFS_PRICE_PER_UNIT") ?></span>
            <?php else: ?>
                <span class="price-value">По договорённости</span>
            <?php endif; ?>
        </div>

        <?php if ($arItem['CAN_BUY']): ?>
            <?php if ($arItem['IN_BASKET']): ?>
                <div class="quantity-controls" id="quantity-controls-<?= $arItem['ID'] ?>">
                    <button class="quantity-btn quantity-minus" onclick="updateQuantity(<?= $arItem['ID'] ?>, -1)">−</button>
                    <span class="quantity-value" id="quantity-<?= $arItem['ID'] ?>"><?= $arItem['BASKET_QUANTITY'] ?></span>
                    <button class="quantity-btn quantity-plus" onclick="updateQuantity(<?= $arItem['ID'] ?>, 1)">+</button>
                    <button class="btn btn-remove" onclick="removeFromBasket(<?= $arItem['ID'] ?>)" title="Удалить из корзины">×</button>
                </div>
                <button onclick="addToBasket(<?= $arItem['ID'] ?>)" class="btn tariff-btn-primary btn-action" id="add-btn-<?= $arItem['ID'] ?>" style="display: none;">
                    <?= $arParams['MESS_BTN_ADD_TO_BASKET'] ?: GetMessage("TARIFFS_ADD_TO_BASKET") ?>
                </button>
            <?php else: ?>
                <div class="quantity-controls" id="quantity-controls-<?= $arItem['ID'] ?>" style="display: none;">
                    <button class="quantity-btn quantity-minus" onclick="updateQuantity(<?= $arItem['ID'] ?>, -1)">−</button>
                    <span class="quantity-value" id="quantity-<?= $arItem['ID'] ?>">0</span>
                    <button class="quantity-btn quantity-plus" onclick="updateQuantity(<?= $arItem['ID'] ?>, 1)">+</button>
                    <button class="btn btn-remove" onclick="removeFromBasket(<?= $arItem['ID'] ?>)" title="Удалить из корзины">×</button>
                </div>
                <button onclick="addToBasket(<?= $arItem['ID'] ?>)" class="btn tariff-btn-primary btn-action" id="add-btn-<?= $arItem['ID'] ?>">
                    <?= $arParams['MESS_BTN_ADD_TO_BASKET'] ?: GetMessage("TARIFFS_ADD_TO_BASKET") ?>
                </button>
            <?php endif; ?>
        <?php else: ?>
            <a href="#" class="btn tariff-btn-primary btn-action"
               data-event="jqm" data-param-id="4" data-name="Оставить заявку">
                Оставить заявку
            </a>
        <?php endif; ?>
    </div>
</div>


<script>

function addToBasket(productId) {
    sendBasketRequest('ADD_TO_BASKET', productId, 1);
}

function updateQuantity(productId, change) {
    sendBasketRequest('UPDATE_QUANTITY', productId, change);
}

function removeFromBasket(productId) {
    if (confirm('Удалить товар из корзины?')) {
        sendBasketRequest('REMOVE_FROM_BASKET', productId, 0);
    }
}

function sendBasketRequest(action, productId, quantity) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/local/ajax/basket_handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    setLoadingState(productId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            setLoadingState(productId, false);
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        updateProductInterface(productId, response);
                        updateBasketCounter(response.basket_count);
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                } catch (e) {
                    alert('Ошибка обработки ответа сервера');
                }
            } else {
                alert('Ошибка сервера (код: ' + xhr.status + ')');
            }
        }
    };
    xhr.send('action=' + encodeURIComponent(action) + '&id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity) + '&iblock_id=<?= $arParams["IBLOCK_ID"] ?>&ajax=Y');
}

function updateProductInterface(productId, response) {
    var addBtn = document.getElementById('add-btn-' + productId);
    var quantityControls = document.getElementById('quantity-controls-' + productId);
    var quantityValue = document.getElementById('quantity-' + productId);
    var removeBtn = document.querySelector('.btn-remove-large');

    if (response.quantity > 0) {
        if (addBtn) addBtn.style.display = 'none';
        if (quantityControls) quantityControls.style.display = 'flex';
        if (removeBtn) removeBtn.style.display = 'inline-block';
        if (quantityValue) quantityValue.textContent = response.quantity;
    } else {	
        if (quantityControls) quantityControls.style.display = 'none';
        if (removeBtn) removeBtn.style.display = 'none';
        if (addBtn) addBtn.style.display = 'inline-block';
    }
}

function setLoadingState(productId, isLoading) {
    var addBtn = document.getElementById('add-btn-' + productId);
    var quantityControls = document.getElementById('quantity-controls-' + productId);
    if (isLoading) {
        if (addBtn && addBtn.style.display !== 'none') { addBtn.disabled = true; addBtn.textContent = 'Обработка...'; }
        if (quantityControls) quantityControls.querySelectorAll('button').forEach(btn => btn.disabled = true);
    } else {
        if (addBtn && addBtn.style.display !== 'none') { addBtn.disabled = false; addBtn.textContent = '<?= $arParams['MESS_BTN_ADD_TO_BASKET'] ?: GetMessage("TARIFFS_ADD_TO_BASKET") ?>'; }
        if (quantityControls) quantityControls.querySelectorAll('button').forEach(btn => btn.disabled = false);
    }
}

function updateBasketCounter(count) {
    document.querySelectorAll('.basket-counter, #basket-count, [data-basket-count]').forEach(counter => counter.textContent = count);
    if (window.BX && window.BX.onCustomEvent) window.BX.onCustomEvent('OnBasketChange', [count]);
}
</script>

