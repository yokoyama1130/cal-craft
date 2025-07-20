サーバー起動

```bash
docker-compose up -d
```

サーバー停止

```bash
docker-compose down
```

マイグレーション
ドッカーに入って
```bash
docker compose exec app bash

```
マイグレーション実行
```bash
bin/cake migrations migrate

```
