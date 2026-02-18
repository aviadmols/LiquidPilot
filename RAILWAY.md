# Railway – התקנה והפעלה

כשהפרויקט נטען ב-Railway, הגדר את הדברים הבאים כדי שהמסד והאפליקציה יעבדו אוטומטית.

## 1. הוספת מסד נתונים (Postgres או MySQL)

- ב-Railway: **New** → **Database** → **Postgres** (או MySQL).
- Railway ייצור משתנה `DATABASE_URL` (או Postgres מספק `DATABASE_URL`).

## 2. משתני סביבה (Variables)

ב-**Variables** של השירות של האפליקציה הוסף לפחות:

| משתנה        | ערך / הערה |
|-------------|------------|
| `APP_KEY`   | הרץ מקומית `php artisan key:generate` והדבק את הערך, או השאר ריק והסקריפט ייצור בהפעלה הראשונה. |
| `APP_ENV`   | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL`   | ה-URL ש-Railway נותן (למשל `https://xxx.railway.app`) |
| `DB_CONNECTION` | `pgsql` (אם השתמשת ב-Postgres) או `mysql` |
| `DB_URL`    | אם הוספת Postgres: `${{Postgres.DATABASE_URL}}` (הפניה למשתנה של שירות ה-DB). אם MySQL: כתובת ה-URL שמספק שירות MySQL. |
| `LOG_CHANNEL` | `stderr` (מומלץ ב-Railway) |

## 3. פקודת Pre-Deploy (התקנת DB ו-seed)

ב-**Settings** → **Deploy** → **Pre-Deploy Command** הזן:

```bash
chmod +x ./railway/init-app.sh && ./railway/init-app.sh
```

זה יריץ בכל דיפלוי:

- `php artisan migrate --force`
- `php artisan db:seed --force`
- ואם אין `APP_KEY` – יצירת מפתח.

אחרי השמירה, Railway יריץ את הפקודה ואז יעלה את האפליקציה.

## 4. Build (אם צריך)

אם יש לך frontend (Vite וכו') – ב-**Build** אפשר להוסיף **Custom Build Command**:

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

(אחרת Nixpacks כבר יריץ `composer install`.)

## סיכום

- **מסד**: Postgres/MySQL כ־Service, משתנים `DB_CONNECTION` ו־`DB_URL`.
- **Pre-Deploy**: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
- **Variables**: `APP_KEY`, `APP_URL`, `APP_ENV`, `LOG_CHANNEL`, וחיבור ל-DB.

לאחר הדיפלוי הראשון, המסד יהיה מותקן (migrate + seed) והאפליקציה תעלה.
