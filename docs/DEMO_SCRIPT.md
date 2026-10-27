# Demo script (8 se 10 minute)

**Tayyari (presentation se pehle):**
1. `database/vaccination.sql` ek baar fresh import karo.
2. Teen browser tabs / profiles: Admin, Parent, Hospital (alag alag login, warna logout hota rahega).
3. Wifi band karke ek baar check karo ke design theek hai (offline assets).
4. Zoom 100%, browser full screen.

| Waqt | Kaun | Kya dikhana hai |
|---|---|---|
| 0:00 | Member 1 | **Landing page:** masla, hal, features, scroll animation, FAQ. 30 second. |
| 0:45 | Member 4 | **Parent login** (ali@example.com / 123456). Dashboard: peela reminder box (Hamza ki appointments aaj aur 3 din baad), lal "due" alert, Hamza ka schedule (Overdue, Booked, Upcoming). Batao: "ye schedule bache ki DOB se khud banta hai." Phir logout. |
| 2:00 | Member 4 | **Parent login** (fatima@example.com / 123456) → **Book Appointment** (Inaya, vaccine: Typhoid) (Fatima Lahore ki hai, isliye **Green Crescent (Lahore) "Near you"** sab se upar aata hai, ye zaroor dikhao): dropdown mein sirf due vaccines, hospital search, tareekh kal se, waqt slot. Submit: "Awaiting approval". |
| 3:30 | Member 3 | **Admin login** (admin@vaccine.com / admin123). Dashboard ke charts. **Requests** kholo, abhi booki hui request **Approve** karo. Unavailable vaccine wali request approve karke rule dikhao. |
| 5:00 | Member 2 | Admin: Hospitals CRUD (ek add karke dikhao), Vaccines (availability toggle), Reports (date filter, Print, CSV). |
| 6:30 | Member 5 | **Hospital login** (city@hospital.com / 123456). Aaj ki 2 appointments. **Hamza ki Pentavalent 2: Vaccinated** mark karo. Aage ki appointment par "Opens on appointment day" dikhao. |
| 7:30 | Member 4 | Parent (ali@example.com): dashboard par Hamza ki Pentavalent 2 ab **Vaccinated**. Vaccination Report, **Certificate** (Ayesha ka), Print / Save as PDF. |
| 8:30 | Member 1 | **Security aur responsive:** parent se admin URL kholo (wapas bhej diya jata hai); F12 mobile view mein tables ka cards banna. |
| 9:30 | Member 1 | Limitations aur future work, dhanyawad. |

**Agar kuch na chale:** ghabrana nahi. Page refresh karo (Ctrl + F5), aur agar data ajeeb ho to SQL dobara import karo (2 minute).

---
## Bonus (2 se 3 minute, agar waqt ho): Day 16 ke naye features

| Kaun | Kya dikhana hai |
|---|---|
| Member 3 | **Naya hospital register karo** (jhoota naam). Login karne ki koshish: "waiting for admin approval". Admin (Users) mein jaa kar **Approve**. Ab login chalta hai. Dikhao ke approve se pehle wo parents ki booking list mein nahi tha. |
| Member 4 | **Parent (ali)** > Past Vaccinations > Hamza ki koi vaccine darj karo. Dashboard par wo "lag chuki" ho jati hai. Admin (Past Records) mein **Verify**. |
| Member 1 | **Login lockout:** kisi demo account par 5 baar galat password, "locked for 5 minutes" ka message. (Phir 5 minute intezar ya admin Users mein Disable aur Enable se lock khul jata hai.) |
| Member 5 | **Certificate verify:** Ayesha ka certificate kholo, uska number copy karo, `verify.php` par daalo: "valid". Number mein ek akshar badal do: "not valid". |

**Toast aur confirm (30 second):** koi bhi delete dabao (jaise parent ka koi bacha ya admin ka koi hospital): modal aayega. Confirm karo: kone mein hara toast.

**Hospital request (1 minute):** sara@example.com se Book Appointment: shehar mein koi hospital na ho to "Suggest a hospital" dikhao. Admin ke **Hospital Requests** mein "Indus Kids Clinic" ko **Approve & add** karo. Sara ki list mein wo "Near you" ke saath aa jata hai.
