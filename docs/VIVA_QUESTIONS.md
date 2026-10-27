# Viva ke sawal aur jawab (poori team yaad kare)

**Tareeqa:** har sawal ka jawab 2 se 3 jumlon mein do. Agar na pata ho to imaandari se kaho "is hissay par hum ne ye kiya", aur apne module ki taraf le aao.

---
## A. Project ka bunyadi taaruf
1. **Ye project kya hai?** Ek web system jahan parents bachon ki vaccination online manage karte hain, hospital appointments record karte hain, aur admin sab control karta hai.
2. **Masla kya tha?** Manual system mein bache vaccine miss kar dete the, aur har bache ka intezar karna waqt leta tha.
3. **Users kitne aur kaun?** Teen: Admin, Parent, Hospital.
4. **Poora flow batao.** Parent bacha add karta hai, due vaccine dekhta hai, appointment book karta hai (pending), admin approve karta hai, hospital ko wo appointment dikhti hai, vaccine lagne par hospital "vaccinated" likhta hai, parent ko report aur certificate milta hai.
5. **Aap ka project doosri teams se alag kaise hai?** Smart schedule (DOB se due vaccine), dashboard reminders, certificate, charts, slot capacity, aur security (CSRF, ownership checks).

## B. Technology
6. **Kaun si technologies?** PHP (PDO), MySQL, HTML, CSS, JavaScript, Bootstrap 5, Chart.js, XAMPP.
7. **Framework kyun nahi (Laravel)?** Plain PHP se hum ne har cheez khud samjhi aur likhi, aur project ka size chhota hai.
8. **Bootstrap kyun?** Ready components aur grid, jis se responsive design asaan aur professional banta hai.
9. **PDO kyun, mysqli kyun nahi?** PDO mein prepared statements chhote code mein aate hain, to galti ka chance kam, aur ye modern standard hai.

## C. Database
10. **Kitni tables?** 5: users, hospitals, children, vaccines, bookings.
11. **Teeno roles ka login ek table mein kyun?** `role` column se pehchan hoti hai, login ka code duplicate nahi hota.
12. **bookings mein do status kyun?** `approval_status` admin ka faisla, `vaccination_status` hospital ka; dono alag log alag waqt badalte hain.
13. **Foreign key aur CASCADE?** Tables ko jodte hain; parent delete ho to uske bache aur bookings bhi khud delete.
14. **Vaccine ki umar din mein kyun?** Din ek hi unit hai: DOB + din = due date, aur compare asaan.
15. **JOIN kya hai?** Do ya zyada tables ko common column se jod kar ek result dena (jaise booking ke saath bache aur hospital ka naam).
16. **Transaction kyun?** Jahan do tables ek saath badalte hain (hospital register) wahan ya dono hon ya koi nahi.

## D. Security
17. **Password kaise save?** `password_hash()` se bcrypt hash, plain text nahi; login par `password_verify()`.
18. **SQL injection kya hai, kaise roka?** Hacker input mein SQL likh deta hai. Humne prepared statements (`?`) use kiye, to input kabhi SQL ka hissa nahi banta.
19. **XSS kya hai?** Input mein `<script>` daal dena. Hum har output `e()` (htmlspecialchars) se guzarte hain.
20. **CSRF kya hai?** Dusri website user ke browser se hamare form ko chori se submit karwa de. Har form mein secret token hai jo server check karta hai.
21. **IDOR kya hai?** URL mein id badal kar kisi aur ka data dekh lena. Har query mein ownership (`AND parent_id = ?`) hai.
22. **Role check kaise?** Har protected page ki pehli line `require_role('admin')`, galat role ko apne dashboard par wapas bhej dete hain.
23. **Session ki hifazat?** HttpOnly aur SameSite cookie, login par session ID naya (`session_regenerate_id`).
24. **Validation kahan?** Browser mein jaldi feedback ke liye, aur **server par asli hifazat** ke liye.
25. **Delete GET se kyun nahi?** GET se data nahi badalna chahiye (koi chhupa link delete karwa sakta hai); POST + CSRF token.
26. **Admin ko register kyun nahi karne dete?** Warna koi bhi khud ko admin bana leta; admin database mein pehle se hai.

## E. Features aur logic
27. **Due vaccine kaise pata chalta hai?** Due date = DOB + recommended age (din). Aaj ki tareekh se compare: guzar chuki to Due/Overdue (30 din ka grace), aage ki to Upcoming; booking ho to Booked; lag chuki to Vaccinated.
28. **Reminder kaise?** Dashboard par wo appointments jo agle 7 din mein hain, "Tomorrow", "In 3 days" ke label ke saath.
29. **Booking ke qaide?** Vaccine due ho aur available ho, pehle se book na ho, kal se 90 din ke andar tareekh, valid slot, usi din doosri appointment na ho, hospital slot mein 5 se zyada nahi.
30. **Unavailable vaccine approve kyun nahi?** Jo vaccine hospital ke paas hai hi nahi, uski appointment dena ghalat hai.
31. **Vaccine delete kyun nahi hoti agar use ho chuki?** Bachon ka vaccination record ud jata; "Unavailable" mark karte hain.
32. **Hospital aage ki appointment ka status kyun nahi badal sakta?** Jo vaccine abhi lagi nahi uski "lag gayi" entry jhooti hoti.
33. **Status lock kyun?** Medical record hai, baad mein badlaav se fraud ya galti ho sakti hai.
34. **Certificate kaise banta hai?** Alag print-friendly page aur CSS `@page` (A4); browser ka Save as PDF; sirf approved aur lagi hui vaccines.
35. **Charts kaise?** Chart.js; PHP data nikal kar `json_encode` se JavaScript ko deta hai, `JSON_HEX_TAG` se script injection se bachaav.
36. **CSV export mein `csv_safe` kyun?** Excel `=` se shuru text ko formula bana kar chala sakta hai (CSV injection).

## F. Responsive aur design
37. **Responsive kaise banaya?** Bootstrap grid, viewport meta tag, aur mobile par tables ko CSS se cards mein badalna (`table-stack` aur `data-label`).
38. **Offline kaise chalta hai?** Libraries ki local copy `assets/vendor` mein; code pehle local file dekhta hai, warna CDN.
39. **Animation kaise?** `IntersectionObserver` se element screen mein aate hi CSS class; `prefers-reduced-motion` ka khayal.

## G. Naye features (Day 16)
43. **Purani vaccinations kyun darj karwate ho?** Warna jis bache ko pehle se vaccines lag chuki hon, system use "overdue" dikhata. Parent darj karta hai, admin verify karta hai, aur certificate mein sirf verified records aate hain.
44. **Parent jhoot likh de to?** Schedule mein foran "lag chuki" ginti hai (parent ke apne faide ke liye), lekin certificate sirf admin ke verify kiye hue records par banta hai, aur har record par "awaiting verification" likha rehta hai.
45. **Naya hospital kaise manzoor hota hai?** Register karte hi status `pending` hota hai: login nahi kar sakta aur parents ki booking list mein nazar nahi aata. Admin Users page se approve kare to `active`.
46. **Admin kisi account ko band kare to?** Har page par `require_login()` database se status dekhta hai, to chalta hua session bhi foran khatam ho jata hai.
47. **Brute-force se kaise bachaya?** 5 galat passwords ke baad us account par 5 minute ka lock (`failed_logins` aur `locked_until`). Lock ke dauran password check hi nahi hota.
48. **Certificate jaali kaise nahi ban sakta?** Number bache ki id aur ek secret (`CERT_SECRET`) se HMAC signature ke saath bana hai. Secret ke baghair koi sahi number nahi bana sakta, aur `verify.php` number ko dobara hisab laga kar check karta hai.
49. **Verify page par poora naam kyun nahi?** Privacy: public page par sirf chhota naam ("Hamza A.") aur saal-e-paidaish dikhate hain.
50. **Pagination mein `LIMIT` ke saath placeholder kyun nahi?** `LIMIT` ko number chahiye, string nahi. Isliye sirf `(int)` kiye hue numbers seedha jodte hain, jo mehfooz hain.

## G2. Day 17-18 ke naye features
51. **Toast aur alert mein kya farq, aur aap ne toast kyun lagaya?** Alert page ke andar jagah ghera rehta hai aur khud nahi jata. Toast kone mein aata hai, 5 second baad khud band hota hai, aur layout ko nahi hilata. Success, error, warning har ek ka apna rang aur icon hai.
52. **Form ki galtiyan toast mein kyun nahi?** Parent ko ghalat khane dekhte hue list parh kar theek karna hota hai; toast ghayab ho jata. Isliye multi-line validation inline rakhi, baqi notifications toast mein.
53. **Confirm popup kaise badla?** Browser ka `confirm()` ki jagah Bootstrap modal, jo `data-confirm` attribute se khud chalta hai; code mein har button par alag kuch nahi likhna padta.
54. **"Near you" kaise kaam karta hai?** Parent ka shehar profile mein hai; booking page par hospitals ka `location` us se milaya jata hai (chhote/bade haroof ka farq nahi), aur milne wale pehle aate hain.
55. **Parent register ke waqt hospital kyun nahi chunta?** Har hospital mein har vaccine nahi hoti aur parent ghar badal sakta hai; hospital booking ke waqt chuna jata hai, aur har booking ki manzoori admin deta hai.
56. **Hospital ke form mein license number kyun?** Admin ko approve karne se pehle asli hospital pehchanne ka tareeqa chahiye; sirf naam aur phone se jhoota hospital bhi register ho sakta hai.

## G3. Day 19: hospital requests
57. **Agar parent ke shehar mein koi hospital na ho to?** Booking page par saaf likha aata hai "no hospital in your city yet" aur "Suggest a hospital" ka button. Parent naam, shehar, address bhejta hai; admin verify karke list mein jorta hai.
58. **Suggested hospital seedha list mein kyun nahi aata?** Warna koi bhi jhoota ya ghalat naam wala hospital list mein aa jata. Admin pehle verify karta hai.
59. **Spam se kaise bachaya?** Ek parent ki ek waqt mein 3 se ziyada requests review mein nahi, aur jo hospital pehle se list mein ya review mein ho uski dobara request nahi.
60. **Approve karne par kya hota hai?** `hospitals` mein naya record (user_id khali, yani abhi uska apna login nahi), request approved, aur parent ke dashboard par "good news" ka banner 7 din ke liye. Agar hospital pehle se list mein ho to naya record nahi banta.
61. **Jis hospital ka login nahi, uski vaccination kaun likhega?** Admin, bookings page se (sirf jab appointment ka din aa chuka ho). Jis hospital ka apna login hai uska status sirf wohi likh sakta hai.
62. **Khali report par print band kyun?** Khali page chhapna bekar hai; button `disabled` ho jata hai jab chhapne ko kuch na ho.

## H. Imaandari wale sawal (sab se zaroori)
40. **Aap ke system ki kamzoriyan?** Lockout sirf account par hai (IP par nahi), email verification aur password reset nahi, slot booking mein chhota race condition, recorded status badalne ka tareeka nahi, SMS/email notifications nahi, aur sirf English interface.
41. **Agar aap ko aur waqt milta?** IP-based rate limiting, email verification, audit log, notifications, Urdu/English toggle, aur admin ke liye status correction.
42. **Ye sab code kisne likha?** (Sach batao.) Hum ne AI assistant ki madad se seekha aur banaya, har file ko samjha, test kiya, aur apne modules ko explain kar sakte hain.
