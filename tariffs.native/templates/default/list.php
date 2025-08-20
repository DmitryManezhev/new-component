<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->addExternalCss($this->getFolder() . "/style.css");

if (empty($arResult["ITEMS"])) {
    echo '<div class="tariffs-empty">' . GetMessage("TARIFFS_NO_ITEMS") . '</div>';
    return;
}
?>

<div class="tariffs-list">
    <?php foreach ($arResult["ITEMS"] as $arItem): ?>

        <div class="tariff-card" data-product-id="<?= $arItem['ID'] ?>">
                <div class="tariff-card-inner-wrapper">
            <?php if ($arItem['PREVIEW_PIC']): ?>
                <div class="tariff-image">
                    <img src="<?= $arItem['PREVIEW_PIC'] ?>" alt="<?= htmlspecialchars($arItem['NAME']) ?>" />
                </div>
            <?php endif; ?>

            <div class="tariff-info">
                <h3 class="tariff-title">
                    <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><?= htmlspecialchars($arItem['NAME']) ?></a>
                </h3>
                <?php if ($arItem['PREVIEW_TEXT']): ?>
                    <p class="tariff-description"><?= $arItem['PREVIEW_TEXT'] ?></p>
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

                <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>" class="btn btn-secondary btn-detail">
                    <?= GetMessage("TARIFFS_MORE_DETAILS") ?>
                </a>
            </div>
	</div></div>
    <?php endforeach; ?>
</div>
<script>
function addToBasket(productId) {
    sendBasketRequest('ADD_TO_BASKET', productId, 1);
}

function updateQuantity(productId, change) {
    var quantityElement = document.getElementById('quantity-' + productId);
    if (!quantityElement) {
        console.error('Не найден элемент количества для товара:', productId);
        return;
    }
    
    var currentQuantity = parseInt(quantityElement.textContent);
    var newQuantity = currentQuantity + change;
    
    console.log('updateQuantity вызвана:', {
        productId: productId,
        change: change,
        currentQuantity: currentQuantity,
        newQuantity: newQuantity
    });
    
    if (newQuantity <= 0) {
        removeFromBasket(productId);
        return;
    }
    
    // Обновляем интерфейс 
    quantityElement.textContent = newQuantity;
    
    sendBasketRequest('UPDATE_BASKET', productId, newQuantity);
}

function removeFromBasket(productId) {
    if (confirm('Удалить товар из корзины?')) {
        sendBasketRequest('REMOVE_FROM_BASKET', productId, 0);
    }
}

function sendBasketRequest(action, productId, quantity) {
    console.log('sendBasketRequest вызвана:', {
        action: action,
        productId: productId,
        quantity: quantity
    });
    
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
                    console.log('Ответ сервера:', response);
                    
                    if (response.status === 'success') {
                        // Обновляем интерфейс на основе РЕАЛЬНОГО состояния корзины
                        updateProductInterface(productId, response);
                        updateBasketCounter(response.basket_count);
                        
                        console.log('Корзина обновлена. Товар ' + productId + ' теперь имеет количество: ' + response.quantity);
                    } else {
                        alert('Ошибка: ' + response.message);
                        console.error('Server error:', response);
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    console.log('Response text:', xhr.responseText);
                    alert('Ошибка обработки ответа сервера');
                }
            } else {
                console.error('HTTP Error:', xhr.status, xhr.statusText);
                alert('Ошибка сервера (код: ' + xhr.status + ')');
            }
        }
    };
    
    xhr.onerror = function() {
        setLoadingState(productId, false);
        console.error('Network error occurred');
        alert('Ошибка соединения с сервером');
    };
    
    var postData = 'action=' + encodeURIComponent(action) + 
                   '&id=' + encodeURIComponent(productId) + 
                   '&quantity=' + encodeURIComponent(quantity) + 
                   '&iblock_id=<?= $arParams["IBLOCK_ID"] ?>' +
                   '&ajax=Y';
    
    console.log('Отправляемые данные:', postData);
    
    xhr.send(postData);
}

function updateProductInterface(productId, response) {
    var addBtn = document.getElementById('add-btn-' + productId);
    var quantityControls = document.getElementById('quantity-controls-' + productId);
    var quantityValue = document.getElementById('quantity-' + productId);
    
    console.log('updateProductInterface вызвана:', {
        productId: productId,
        responseQuantity: response.quantity,
        hasAddBtn: !!addBtn,
        hasControls: !!quantityControls,
        hasQuantityValue: !!quantityValue
    });
    
    if (response.quantity > 0) {
        if (addBtn) {
            addBtn.style.display = 'none';
        }
        if (quantityControls) {
            quantityControls.style.display = 'flex';
        }
        if (quantityValue) {
            quantityValue.textContent = response.quantity;
            console.log('Обновлено количество в интерфейсе:', response.quantity);
        }
    } else {
        if (quantityControls) {
            quantityControls.style.display = 'none';
        }
        if (addBtn) {
            addBtn.style.display = 'inline-block';
        }
    }
}

function setLoadingState(productId, isLoading) {
    var addBtn = document.getElementById('add-btn-' + productId);
    var quantityControls = document.getElementById('quantity-controls-' + productId);
    
    if (isLoading) {
        if (addBtn && addBtn.style.display !== 'none') {
            addBtn.disabled = true;
            addBtn.textContent = 'Обработка...';
        }
        if (quantityControls) {
            var buttons = quantityControls.querySelectorAll('button');
            buttons.forEach(function(btn) {
                btn.disabled = true;
            });
        }
    } else {
        if (addBtn && addBtn.style.display !== 'none') {
            addBtn.disabled = false;
            addBtn.textContent = '<?= $arParams['MESS_BTN_ADD_TO_BASKET'] ?: GetMessage("TARIFFS_ADD_TO_BASKET") ?>';
        }
        if (quantityControls) {
            var buttons = quantityControls.querySelectorAll('button');
            buttons.forEach(function(btn) {
                btn.disabled = false;
            });
        }
    }
}

function updateBasketCounter(count) {
    var basketCounters = document.querySelectorAll('.basket-counter, #basket-count, [data-basket-count]');
    basketCounters.forEach(function(counter) {
        counter.textContent = count;
    });
    
    if (window.BX && window.BX.onCustomEvent) {
        window.BX.onCustomEvent('OnBasketChange', [count]);
    }
}
</script>
