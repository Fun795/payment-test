<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Тестовое на php backend laravel

## Общая Информация
- Мини-шлюз оплат
- Цель: Реализовать упрощённый API для обработки платежей на фреймворке Laravel PHP.
- Описание: Задача заключается в создании простого шлюза для симуляции платежных операций. Это включает создание модели данных, эндпоинтов API для создания, обработки и просмотра платежей. Проект должен включать в себя валидацию и обработку состояний.

Требования к реализации

1. Модель `Payment`
   Создать Eloquent-модель `Payment` с следующими полями:
- `uuid`: Уникальный идентификатор платежа (строковый тип, генерируется автоматически).
- `amount`: Сумма платежа (десятичное число, с поддержкой дробной части).
- `currency`: Валюта платежа (RUB, EUR, USD).
- `status`: Статус платежа (pending, success, failed).
- `created_at`: Временная метка создания).

Модель должна использовать миграции для создания соответствующей таблицы в базе данных. Рекомендуется использовать MySQL/Postgresql для тестирования.

2. Эндпоинт: `POST /payments`
- Описание: Создание нового платежа.
- Входные Параметры:
    - amount: Обязательный, положительное число, число от 1.
    - currency: Обязательный.
- Логика:
    - Создать новую запись в модели Payment со статусом 'pending'.
    - Сгенерировать и сохранить uuid.
    - Вернуть в ответе только payment_uuid.
- Ответ: JSON с полем `payment_uuid` (HTTP статус 201 Created).
- Ошибки: Обработать валидацию и вернуть ошибки.

3. Эндпоинт: `POST /payments/{uuid}/process`
- Описание: Обработка платежа (симуляция успеха или неудачи).
- Входные Параметры:
    - success: Обязательный, булевый тип (true или false).
- Логика:
    - Найти платеж по `uuid`. Если не найден — вернуть ошибку (HTTP 404 Not Found).
    - Если статус уже не 'pending' — не позволять обработку.
    - Обновить статус на 'success' (если `success = true`) или '`failed`' (если `success = false`).
    - Опционально (плюсик): Логировать событие в отдельную таблицу `payment_logs` (модель PaymentLog с полями: payment_uuid, action, timestamp). Action может быть 'processed_success' или 'processed_failed'.
- Ответ: JSON с обновлённой информацией о платеже или подтверждением (HTTP 200 OK).
- Ошибки: Валидация входа, проверка существования и статуса (HTTP 400 Bad Request или 422).

4. Эндпоинт: GET `/payments/{uuid}`
- Описание: Получение информации о платеже.
- Входные Параметры: Нет (только `{uuid}` в пути).
- Логика:
    - Найти платеж по uuid. Если не найден — вернуть ошибку.
    - Вернуть все поля модели: uuid, amount, currency, status, created_at.
- Ответ: JSON с данными платежа (HTTP 200 OK).

### Дополнительные Требования
- Валидация: Использовать встроенные валидаторы Laravel (Request classes) для всех эндпоинтов. Обеспечить обработку ошибок с понятными сообщениями.
- Статусы: Реализовать логику переходов статусов (только из 'pending' в 'success' или 'failed'). Избегать недопустимых переходов.
- Idempotency: Обеспечить, чтобы повторные вызовы /process не изменяли статус, если он уже обработан. Можно использовать проверки в контроллере.
- Работа с Ресурсами: Использовать Resource classes для форматирования JSON-ответов (опционально, но рекомендуется для чистоты кода).
- Структура Кода:
    - Использовать middleware для rate limiter (не обязательно).
    - Обеспечить чистый, читаемый код, желательно с комментариями.
    - База Данных: Использовать миграции для инициализации.

### Что проверяется
- Качество валидации входных данных.
- Корректность работы со статусами.
- Поддержка idempotency.
- Эффективная работа с ресурсами базы данных.
- Общая структура кода: читаемость, организация, Laravel best practices.

## Запуск

1. `composer install` - Установка пакетов
2. `docker compose up` - Поднять docker контейнеры
3. `php artisan migrate` - Запустить миграции

### Роуты

* POST `/api/payments` - создать платёж
```json
  {
    "amount": 100,
    "currency": "RUB"
  }
```
* PATCH `/api/payments/{paymentUuid}/process` - изменить статус платежа
```json
  {
    "success": true //false
  }
```
* GET `/api/payments/{paymentUuid}` - получить платёж по uuid
