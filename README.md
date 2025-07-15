サーバー起動

```bash
docker-compose up -d
```

サーバー停止

```bash
docker-compose down
```

マイグレーション

```bash
docker compose exec app php vendor/bin/phinx migrate -e development

```
