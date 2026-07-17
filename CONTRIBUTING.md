# Участие в разработке

Предложения, сообщения об ошибках и запросы на улучшение принимаются через
[GitHub Issues](https://github.com/elftorg/keyrights/issues).

Перед созданием issue укажите версию модуля, версию Bitrix/PHP и краткие шаги
для воспроизведения. Не публикуйте пароли, ключевые фразы, cookies и реальные
данные из журнала событий.

Локальные проверки:

```bash
composer validate
composer test
npm ci --prefix install/components/drdroid/keyrights
npm run build --prefix install/components/drdroid/keyrights
```
