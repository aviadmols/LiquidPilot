# תיקון 502 ב-Railway – שלב אחרי שלב

כתובת האתר: https://liquidpilot-production.up.railway.app/

502 = הפרוקסי של Railway לא מקבל תשובה תקינה מהאפליקציה (האפליקציה לא עולה, קורסת, או לא מאזינה על הפורט הנכון).

---

## שלב 1: בדוק ש־Start Command נכון

1. היכנס ל־**Railway Dashboard**: https://railway.app/
2. בחר את הפרויקט ואת **השירות של האפליקציה** (לא את ה-Database).
3. לך ל־**Settings** (או **Variables** → גלול) → חפש **Deploy** / **Custom Start Command**.
4. וודא ש־**Start Command** הוא **בדיוק** אחד מהבאים (העתק-הדבק):

   ```bash
   sh start.sh
   ```

   או:

   ```bash
   php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
   ```

5. **אסור** שיהיה שם:
   - `admin:create`
   - `make:admin-user`
   - או כל פקודת artisan אחרת שדורשת קלט.

6. אם שינית – **שמור** ולחץ **Redeploy** (או Deploy מחדש).

---

## שלב 2: חבר Database (Postgres)

בלי DB האפליקציה עלולה ליפול או שה־Pre-Deploy נכשל.

1. ב־Railway: אם **אין** עדיין Postgres – **New** → **Database** → **Postgres**.
2. **חבר את ה-Database לאפליקציה:**
   - פתח את **שירות האפליקציה** → **Variables**.
   - או: פתח את שירות ה-**Postgres** → **Connect** / **Variables** והעתק את `DATABASE_URL`.
   - בשירות האפליקציה הוסף משתנה:
     - **Name:** `DATABASE_URL`
     - **Value:** הערך מ־Postgres (או השתמש ב־Reference: `${{Postgres.DATABASE_URL}}` אם Railway מציע).
3. **Redeploy** אחרי הוספת/עדכון ה־Variables.

---

## שלב 3: Pre-Deploy Command (מיגרציות + seed)

1. בשירות האפליקציה → **Settings** → **Deploy**.
2. בשדה **Pre-Deploy Command** (או **Custom Pre-Deploy**) הזן **בדיוק**:

   ```bash
   chmod +x ./railway/init-app.sh && ./railway/init-app.sh
   ```

3. שמור ו־**Redeploy**.

הסקריפט מריץ: `migrate --force`, `db:seed --force`, ו־`key:generate` אם אין `APP_KEY`.

---

## שלב 4: משתני סביבה (Variables)

ב־**Variables** של שירות האפליקציה וודא שיש:

| משתנה      | ערך לדוגמה / הערה |
|------------|---------------------|
| `APP_KEY`  | רשום 32 תווים (או השאר ריק – ה־Pre-Deploy ייצר). |
| `APP_ENV`  | `production` |
| `APP_DEBUG`| `false` |
| `APP_URL`  | `https://liquidpilot-production.up.railway.app` |
| `LOG_CHANNEL` | `stderr` (מומלץ) |
| `DATABASE_URL` | מהחיבור ל־Postgres (שלב 2) |

אם אין `APP_KEY` – אחרי Redeploy ה־Pre-Deploy אמור ליצור. אם לא, הרץ מקומית `php artisan key:generate` והדבק ב־Variables.

---

## שלב 5: בדיקת לוגים אחרי Redeploy

1. **Deployments** → בחר את הדיפלוי האחרון.
2. פתח **Deploy Logs** (לוגים בזמן ריצה).
3. חפש:
   - **טוב:** `Starting Laravel on 0.0.0.0:XXXX` – השרת עלה.
   - **רע:** `Run with --password=...` – ה־Start Command עדיין שגוי (חזור לשלב 1).
   - **רע:** שגיאת PHP / Exception – תקן לפי ההודעה (לעיתים קרובות DB או משתנה חסר).

אם יש **Pre deploy command** בלוג – בדוק אם יש `[Pre-deploy] ERROR: migrate failed`. אם כן – בדוק `DATABASE_URL` וחיבור ל־Postgres (שלבים 2 ו־4).

---

## שלב 6: בדיקת health check

אחרי דיפלוי מוצלח:

1. פתח בדפדפן:  
   **https://liquidpilot-production.up.railway.app/up**
2. אם אתה מקבל **200** (או "OK") – האפליקציה עונה.
3. אם עדיין 502 – הבעיה כנראה ב־Start Command או בלוגים (שלב 5).

---

## שלב 7: אם יש לך גישה ל־railway.json ב־repo

בפרויקט יש **railway.json** עם:

- `startCommand: "sh start.sh"`
- `preDeployCommand: "chmod +x ./railway/init-app.sh && ./railway/init-app.sh"`

בגרסאות מסוימות Railway לוקח מכאן. אם אחרי push ו־Redeploy ה־502 נשאר – **הגדר ידנית** את ה־Start Command ואת ה־Pre-Deploy ב־Dashboard (שלבים 1 ו־3).

---

## סיכום קצר

1. **Start Command:** `sh start.sh` (או `php artisan serve --host=0.0.0.0 --port=${PORT:-8080}`) – לא `admin:create`.
2. **Database:** Postgres מחובר ו־`DATABASE_URL` ב־Variables.
3. **Pre-Deploy:** `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
4. **Variables:** `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` (הדומיין שלך).
5. **Redeploy** אחרי כל שינוי משמעותי.
6. **בדוק לוגים** ו־`/up` כדי לראות אם האפליקציה באמת עולה.

אחרי שכל השלבים נכונים – גישה ל־`https://liquidpilot-production.up.railway.app/` (או `/admin`) אמורה לעבוד בלי 502.
