<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ezra's "brain": simple step-by-step guides to the portal, per role and in
 * English, Shona and Ndebele, plus a few Bible study notes and tips of the day.
 *
 * The chat endpoint picks the guides that match a question and gives them to
 * the AI model as knowledge. When the model isn't running, Ezra answers
 * straight from the best matching guide (fallback()).
 *
 * The Shona (sn) and Ndebele (nd) text is simple and should be checked by a
 * native speaker at the Center; edit it freely here.
 */
class Ezra_knowledge
{
    /**
     * Each guide: roles it is for, keywords (any language, lower case), and a
     * title + steps per language. Put the most specific guides first: on a tie
     * the earlier one wins.
     */
    public static $guides = [
        [
            'id' => 'pay', 'roles' => ['student'],
            'keywords' => ['pay', 'payment', 'proof', 'fees', 'fee', 'ecocash', 'bank', 'receipt', 'transfer', 'bhadhara', 'mari', 'kubhadhara', 'ukubhadala', 'imali', 'bhadala'],
            'title' => ['en' => 'How to submit proof of payment', 'sn' => 'Maitiro ekutumira humbowo hwekubhadhara', 'nd' => 'Indlela yokuthumela ubufakazi bokubhadala'],
            'steps' => [
                'en' => ['Pay the program fee (one fee covers every module in the program) by EcoCash or bank transfer (the account details are on the upload page).', 'Take a clear photo or screenshot of the payment message or bank slip.', 'Go to **Programs**, open your program and tap **Submit proof of payment**.', 'Choose the amount and method, add the photo and tap **Submit**.', 'The office checks it. When it is approved all the modules in the program open and you get a receipt under **Payments**. If it is not accepted, the reason is shown and you can upload again.'],
                'sn' => ['Bhadharai mari yeprogram (imwe chete, inovhurira makosi ese) neEcoCash kana kuburikidza nebhangi (ruzivo rweakaundi ruri papeji yekuisa humbowo).', 'Torai mufananidzo wakajeka wemeseji yekubhadhara kana slip yebhangi.', 'Endai ku **Programs**, vhurai program yenyu mobva madzvanya **Submit proof of payment** pa program yenyu.', 'Sarudzai mari nenzira yamakabhadhara nayo, isai mufananidzo modzvanya **Submit**.', 'Hofisi inozviongorora. Kana zvabvumirwa kosi inovhurika uye munowana risiti pa **Payments**.'],
                'nd' => ['Bhadala imali ye-program (eyodwa ivulela zonke izifundo) nge-EcoCash loba ngebhanga (imininingwane ye-akhawunti isekhasini lokulayisha).', 'Thatha isithombe esicacileyo somlayezo wokubhadala loba i-slip yebhanga.', 'Iya ku **Programs**, uvule i-program yakho ubusucindezela **Submit proof of payment** ku-program yakho.', 'Khetha imali lendlela obhadale ngayo, faka isithombe ucindezele **Submit**.', 'Ihhovisi liyakuhlola. Nxa kwamukelwe isifundo siyavuleka njalo uthola irisidi ku **Payments**.'],
            ],
        ],
        [
            'id' => 'approve_pay', 'roles' => ['admin'],
            'keywords' => ['pay', 'payment', 'proof', 'approve', 'reject', 'fees', 'receipt', 'pending', 'bhadhara', 'mari', 'imali', 'bhadala'],
            'title' => ['en' => 'How to approve or reject a payment', 'sn' => 'Maitiro ekubvumira kana kuramba kubhadhara', 'nd' => 'Indlela yokwamukela loba ukwala inkokhelo'],
            'steps' => [
                'en' => ['Open **Payments** in the menu (the badge shows how many are waiting).', 'Look at the proof picture on each card and check the money arrived.', 'Tap **Approve**: every module in the program opens for the student and a receipt is created.', 'Or tap **Reject** and write the reason; the student sees it and can upload again.', 'Old payments and receipts are under **History**.'],
                'sn' => ['Vhurai **Payments** mumenu.', 'Tarisai mufananidzo wehumbowo muone kana mari yasvika.', 'Dzvanyai **Approve**: kosi inovhurirwa mudzidzi.', 'Kana kuti dzvanyai **Reject** monyora chikonzero.'],
                'nd' => ['Vula **Payments** kumenyu.', 'Khangela isithombe sobufakazi ubone ukuthi imali ifikile.', 'Cindezela **Approve**: isifundo siyavulelwa umfundi.', 'Loba cindezela **Reject** ubhale isizatho.'],
            ],
        ],
        [
            'id' => 'enrol', 'roles' => ['student'],
            'keywords' => ['enrol', 'enroll', 'enrolment', 'enrollment', 'apply', 'register', 'join', 'module', 'modules', 'course', 'courses', 'program', 'programs', 'kunyoresa', 'nyoresa', 'kosi', 'ukubhalisa', 'bhalisa', 'isifundo', 'izifundo'],
            'title' => ['en' => 'How to enrol in a program', 'sn' => 'Maitiro ekunyoresa kukosi', 'nd' => 'Indlela yokubhalisela isifundo'],
            'steps' => [
                'en' => ['Open **Programs** (on a phone: **More** then **Programs**).', 'Tap a program to see its modules and its fee. One fee covers every module in the program. Tap **Apply now**.', 'Pay the fee and upload your proof of payment (ask me "how do I pay?").', 'When the office approves your payment every module in the program opens: materials, assignments and exams appear.', 'You may join more than one program.'],
                'sn' => ['Vhurai **Programs** (parunhare: **More** wozoti **Programs**).', 'Dzvanyai program yacho muone makosi ayo nemari yayo (imwe chete yemakosi ese). Modzvanya **Apply now**.', 'Bhadharai motumira humbowo hwekubhadhara.', 'Hofisi yabvumira, kosi inovhurika.'],
                'nd' => ['Vula **Programs** (efonini: **More** bese **Programs**).', 'Cindezela i-program ubone izifundo zayo. Bona imali yayo (eyodwa yezifundo zonke), ubusucindezela **Apply now**.', 'Bhadala ubusuthumela ubufakazi bokubhadala.', 'Nxa ihhovisi selivumile, isifundo siyavuleka.'],
            ],
        ],
        [
            'id' => 'grades', 'roles' => ['student'],
            'keywords' => ['grade', 'grades', 'mark', 'marks', 'marked', 'score', 'feedback', 'assignment', 'assignments', 'mamaki', 'maki', 'amaphuzu', 'imaki', 'basa'],
            'title' => ['en' => 'How to check your assignment marks', 'sn' => 'Maitiro ekuona mamaki eassignment', 'nd' => 'Indlela yokubona amaphuzu e-assignment'],
            'steps' => [
                'en' => ['Open **Assignments** in the menu or bottom bar.', 'Go to the **Handed in** tab.', 'Marked work shows your mark, the percentage and your lecturer\'s feedback.', '"Waiting to be marked" means the lecturer hasn\'t marked it yet; you get an alert when they do.', 'Your overall module result is on the **Results** page once the lecturer publishes it.'],
                'sn' => ['Vhurai **Assignments**.', 'Endai ku **Handed in**.', 'Basa rakamakwa rinoratidza mamaki enyu nemashoko emudzidzisi.', 'Munowana alert kana ramakwa.'],
                'nd' => ['Vula **Assignments**.', 'Iya ku **Handed in**.', 'Umsebenzi osumakiwe utshengisa amaphuzu akho lamazwi omfundisi.', 'Uthola i-alert nxa usumakiwe.'],
            ],
        ],
        [
            'id' => 'submit', 'roles' => ['student'],
            'keywords' => ['submit', 'hand in', 'handin', 'upload assignment', 'due', 'deadline', 'late', 'kutumira', 'tumira', 'ukuthumela', 'thumela'],
            'title' => ['en' => 'How to hand in an assignment', 'sn' => 'Maitiro ekutumira assignment', 'nd' => 'Indlela yokuthumela i-assignment'],
            'steps' => [
                'en' => ['Open **Assignments** and tap the assignment.', 'Read the instructions (and the question paper, if there is one).', 'Attach your document (up to 10 MB) and/or type your answer.', 'Tap **Hand in**. You can replace it until the due date.', 'After the due date only a first hand-in is accepted, and only if the lecturer allows late work.'],
                'sn' => ['Vhurai **Assignments** modzvanya assignment yacho.', 'Isai gwaro renyu kana kunyora mhinduro.', 'Dzvanyai **Hand in** zuva rekupedzisira risati rasvika.'],
                'nd' => ['Vula **Assignments** ucindezele i-assignment.', 'Faka idokhumente yakho loba ubhale impendulo.', 'Cindezela **Hand in** ngaphambi kosuku lokucina.'],
            ],
        ],
        [
            'id' => 'set_assignment', 'roles' => ['lecturer'],
            'keywords' => ['assignment', 'assignments', 'set', 'create', 'upload', 'homework', 'mark', 'marking', 'basa', 'umsebenzi'],
            'title' => ['en' => 'How to set (upload) an assignment', 'sn' => 'Maitiro ekuisa assignment', 'nd' => 'Indlela yokufaka i-assignment'],
            'steps' => [
                'en' => ['Open **Assignments** and tap **New assignment** on the module.', 'Type the title and instructions; attach a question paper if you have one.', 'Set the due date and time, the marks it is out of, and whether late work is accepted.', 'Tap **Save**. Paid-up students on the module are notified.', 'To mark: open the assignment, open each student\'s work, give a mark and feedback, and save.'],
                'sn' => ['Vhurai **Assignments** modzvanya **New assignment**.', 'Nyorai zita nemirairo, isai zuva rekupedzisira nemamaki.', 'Dzvanyai **Save**; vadzidzi vanoziviswa.'],
                'nd' => ['Vula **Assignments** ucindezele **New assignment**.', 'Bhala ibizo lemilayo, ufake usuku lokucina lamaphuzu.', 'Cindezela **Save**; abafundi bayaziswa.'],
            ],
        ],
        [
            'id' => 'create_exam', 'roles' => ['lecturer'],
            'keywords' => ['exam', 'exams', 'test', 'quiz', 'create', 'question', 'questions', 'publish', 'invigilate', 'bvunzo', 'miedzo', 'uhlolo', 'ukuhlolwa'],
            'title' => ['en' => 'How to create an online exam', 'sn' => 'Maitiro ekugadzira bvunzo', 'nd' => 'Indlela yokwenza uhlolo'],
            'steps' => [
                'en' => ['Open **Exams** and tap **New exam** on your module.', 'Set the window (opens / closes), the time allowed and, if you like, a question pool and shuffling.', 'Add questions: multiple choice (marked automatically) or short answer (you mark them).', 'Tap **Publish**. Students are notified. You can\'t change questions once someone starts.', 'Use **Invigilate** while it runs; afterwards mark the short answers and tap **Release results**.'],
                'sn' => ['Vhurai **Exams** modzvanya **New exam**.', 'Isai nguva yekuvhura nekuvhara nenguva yekunyora.', 'Wedzerai mibvunzo, modzvanya **Publish**.'],
                'nd' => ['Vula **Exams** ucindezele **New exam**.', 'Faka isikhathi sokuvula lokuvala lesikhathi sokubhala.', 'Engeza imibuzo, ucindezele **Publish**.'],
            ],
        ],
        [
            'id' => 'admin_exams', 'roles' => ['admin'],
            'keywords' => ['exam', 'exams', 'test', 'create', 'bvunzo', 'uhlolo', 'lecturer', 'assign'],
            'title' => ['en' => 'How exams are created (admin)', 'sn' => 'Magadzirirwo anoitwa bvunzo', 'nd' => 'Indlela uhlolo olwenziwa ngayo'],
            'steps' => [
                'en' => ['Exams are written by the module\'s lecturer, so first make sure the module has one.', 'Open **Programs**, open the program, find the module and **assign a lecturer** (add one under **Lecturers** if needed).', 'The lecturer then opens **Exams** > **New exam**, adds the questions and publishes it.', 'Students on the module are notified and can sit it in the time window.', 'You can follow results under **Results** and **Reports**.'],
                'sn' => ['Bvunzo dzinogadzirwa nemudzidzisi wekosi.', 'Vhurai **Programs**, vhurai program, mupe kosi mudzidzisi.', 'Mudzidzisi anovhura **Exams** > **New exam**.'],
                'nd' => ['Uhlolo lwenziwa ngumfundisi wesifundo.', 'Vula **Programs**, uvule i-program, unike isifundo umfundisi.', 'Umfundisi uvula **Exams** > **New exam**.'],
            ],
        ],
        [
            'id' => 'programs', 'roles' => ['admin'],
            'keywords' => ['program', 'programs', 'programme', 'module', 'modules', 'course', 'courses', 'diploma', 'create', 'add', 'lecturer', 'assign', 'kosi', 'makosi', 'isifundo', 'izifundo'],
            'title' => ['en' => 'How to add a program and its modules', 'sn' => 'Maitiro ekuwedzera program nemakosi ayo', 'nd' => 'Indlela yokwengeza i-program lezifundo zayo'],
            'steps' => [
                'en' => ['Open **Programs** in the menu. A program is a qualification, for example a Diploma in Theology.', 'Type the name, the program fee (one price for the whole program, for example $300), the duration and a short description, then tap **Add program**.', 'Tap **Modules** on the program, then add each module with (if you like) a code and credits. Modules have no fee of their own.', 'On each module card, choose a lecturer and tap **Assign**. Use the arrows to set the order the modules are shown in.', 'Students see the program under **Programs**, apply and pay the one fee, and every module opens to them, including modules you add later. Use **Close** to stop new applications without removing anyone.'],
                'sn' => ['Vhurai **Programs** mumenu. Program inzvimbo yedzidzo, semuenzaniso Diploma in Theology.', 'Nyorai zita, nguva yekudzidza nemashoko mashomanana, modzvanya **Add program**.', 'Dzvanyai **Modules** pa program, mowedzera kosi imwe neimwe.', 'Pakadhi rekosi, sarudzai mudzidzisi modzvanya **Assign**.'],
                'nd' => ['Vula **Programs** kumenyu. I-program yikufundela, isibonelo i-Diploma in Theology.', 'Bhala ibizo, isikhathi lencazelo emfitshane, ucindezele **Add program**.', 'Cindezela **Modules** ku-program, wengeze isifundo ngasinye.', 'Ekhadini lesifundo, khetha umfundisi ucindezele **Assign**.'],
            ],
        ],
        [
            'id' => 'write_exam', 'roles' => ['student'],
            'keywords' => ['exam', 'exams', 'test', 'quiz', 'write', 'sit', 'start', 'when', 'bvunzo', 'miedzo', 'uhlolo', 'ukuhlolwa'],
            'title' => ['en' => 'How to write an online exam', 'sn' => 'Maitiro ekunyora bvunzo painternet', 'nd' => 'Indlela yokubhala uhlolo ku-inthanethi'],
            'steps' => [
                'en' => ['Open **Exams**: "Open now" shows exams you can start; "Coming up" shows dates.', 'Tap the exam, read the rules and accept the integrity pledge, then **Start**.', 'The timer runs from when you start. Answers save by themselves, even if the signal drops.', 'Stay on the exam page: leaving it, pasting or copying is reported to your lecturer.', 'Tap **Hand in** when done (it hands in by itself at zero). Results appear when the lecturer releases them.'],
                'sn' => ['Vhurai **Exams**: "Open now" inoratidza bvunzo dzamunogona kutanga.', 'Verengai mitemo, bvumai pledge, modzvanya **Start**.', 'Mhinduro dzinozvichengeta dzega. Musabuda papeji.', 'Dzvanyai **Hand in** kana mapedza.'],
                'nd' => ['Vula **Exams**: "Open now" itshengisa uhlolo ongaluqala.', 'Bala imithetho, wamukele i-pledge, ucindezele **Start**.', 'Izimpendulo ziyazigcina. Ungaphumi ekhasini.', 'Cindezela **Hand in** nxa usuqedile.'],
            ],
        ],
        [
            'id' => 'upload_id', 'roles' => ['student', 'lecturer'],
            'keywords' => ['id', 'national id', 'identity', 'passport', 'document', 'documents', 'qualification', 'certificate', 'upload id', 'chitupa', 'gwaro', 'magwaro', 'isitupa', 'amaphepha', 'incwadi'],
            'title' => ['en' => 'How to upload your ID or documents', 'sn' => 'Maitiro ekuisa chitupa kana magwaro', 'nd' => 'Indlela yokulayisha isitupa loba amaphepha'],
            'steps' => [
                'en' => ['Open **My documents** in the menu (a badge shows if something required is missing).', 'The checklist at the top shows what the Center needs (e.g. National ID).', 'Choose the document type, add a clear photo or PDF (up to 5 MB) and tap **Upload**.', 'It shows **Waiting** until the office checks it, then **Verified**.', 'If it is **Not accepted**, read the reason and upload a clearer copy.'],
                'sn' => ['Vhurai **My documents** mumenu.', 'Sarudzai rudzi rwegwaro, isai mufananidzo wakajeka kana PDF (kusvika 5 MB), modzvanya **Upload**.', 'Rinoratidza **Waiting** kusvika hofisi yariongorora, rozoti **Verified**.', 'Kana risina kugamuchirwa, verengai chikonzero moisa rimwe rakajeka.'],
                'nd' => ['Vula **My documents** kumenyu.', 'Khetha uhlobo lwephepha, faka isithombe esicacileyo loba i-PDF (kuze kube ngu-5 MB), ucindezele **Upload**.', 'Kutshengisa **Waiting** ize ihhovisi ilihlole, bese **Verified**.', 'Nxa lingamukelwanga, bala isizatho ulayishe elicacileyo.'],
            ],
        ],
        [
            'id' => 'verify_docs', 'roles' => ['admin'],
            'keywords' => ['document', 'documents', 'id', 'verify', 'qualification', 'national id', 'missing', 'gwaro', 'chitupa', 'isitupa', 'amaphepha'],
            'title' => ['en' => 'How to verify ID copies and qualifications', 'sn' => 'Maitiro ekuongorora magwaro', 'nd' => 'Indlela yokuhlola amaphepha'],
            'steps' => [
                'en' => ['Open **Documents** in the menu (the badge shows how many are waiting).', 'Check each copy (images preview on the card; tap to open a PDF).', 'Tap **Verify**, or **Reject** with a reason the person will see.', 'The **Missing** tab lists people who still owe a required document, with a WhatsApp **Remind** button.', 'Choose which documents are required for students and lecturers at the bottom of the page.'],
                'sn' => ['Vhurai **Documents** mumenu.', 'Ongororai gwaro rimwe nerimwe modzvanya **Verify** kana **Reject** nechikonzero.'],
                'nd' => ['Vula **Documents** kumenyu.', 'Hlola iphepha ngalinye ucindezele **Verify** loba **Reject** ngesizatho.'],
            ],
        ],
        [
            'id' => 'results', 'roles' => ['student'],
            'keywords' => ['result', 'results', 'statement', 'transcript', 'pass', 'passed', 'fail', 'certificate', 'mhedzisiro', 'zvibodzwa', 'imiphumela', 'phumelele'],
            'title' => ['en' => 'How to see your results and print a statement', 'sn' => 'Maitiro ekuona mhedzisiro yenyu', 'nd' => 'Indlela yokubona imiphumela yakho'],
            'steps' => [
                'en' => ['Open **Results** in the menu (on a phone: **More** then **Results**).', 'Each module shows your final %, grade, remarks and what the mark is made of.', 'Results appear only after your lecturer publishes them.', 'Tap **Statement of results** to print it or save it as PDF. Its QR code lets anyone check it is genuine.'],
                'sn' => ['Vhurai **Results**.', 'Kosi imwe neimwe inoratidza % nemagiredhi.', 'Dzvanyai **Statement of results** kuti muprinte.'],
                'nd' => ['Vula **Results**.', 'Isifundo ngasinye sitshengisa i-% legreyidi.', 'Cindezela **Statement of results** ukuze uprinte.'],
            ],
        ],
        [
            'id' => 'attendance', 'roles' => ['lecturer'],
            'keywords' => ['attendance', 'register', 'roll', 'present', 'absent', 'class', 'kupinda', 'ukungena', 'irejista'],
            'title' => ['en' => 'How to take the register (attendance)', 'sn' => 'Maitiro ekutora rejista', 'nd' => 'Indlela yokuthatha irejista'],
            'steps' => [
                'en' => ['Open **Attendance** and tap **Take register** on the module.', 'Check the date and add the topic.', 'Everyone starts as present: tap **Late**, **Absent** or **Excused** where needed.', 'Tap **Save**. You can correct it later.', 'The module page shows each student\'s rate; below 75% you get a WhatsApp check-in button.'],
                'sn' => ['Vhurai **Attendance** modzvanya **Take register**.', 'Vese vanotanga vari "present"; dzvanyai **Absent** kana **Late**.', 'Dzvanyai **Save**.'],
                'nd' => ['Vula **Attendance** ucindezele **Take register**.', 'Bonke baqala be "present"; cindezela **Absent** loba **Late**.', 'Cindezela **Save**.'],
            ],
        ],
        [
            'id' => 'my_attendance', 'roles' => ['student'],
            'keywords' => ['attendance', 'register', 'absent', 'present', 'kupinda', 'ukungena'],
            'title' => ['en' => 'How to check your attendance', 'sn' => 'Maitiro ekuona kupinda kwenyu makirasi', 'nd' => 'Indlela yokubona ukungena kwakho emakilasini'],
            'steps' => [
                'en' => ['Open **Attendance** (on a phone: **More** then **Attendance**).', 'Each module shows your attendance rate and every class mark.', 'Excused absences don\'t count against you. Talk to your lecturer if a mark is wrong.'],
                'sn' => ['Vhurai **Attendance**.', 'Kosi imwe neimwe inoratidza chiyero chekupinda kwenyu.'],
                'nd' => ['Vula **Attendance**.', 'Isifundo ngasinye sitshengisa izinga lokungena kwakho.'],
            ],
        ],
        [
            'id' => 'calendar', 'roles' => ['student', 'lecturer', 'admin'],
            'keywords' => ['calendar', 'timetable', 'schedule', 'event', 'events', 'class', 'classes', 'holiday', 'date', 'dates', 'karenda', 'zuva', 'ikhalenda', 'usuku'],
            'title' => ['en' => 'How to use the calendar', 'sn' => 'Maitiro ekushandisa karenda', 'nd' => 'Indlela yokusebenzisa ikhalenda'],
            'steps' => [
                'en' => ['Open **Calendar** (under Campus; on a phone: **More** then **Calendar**).', 'The month grid shows classes, events and holidays; assignment due dates and exam times appear by themselves.', 'Tap a day to see its list; online classes have a **Join** link.', 'Lecturers and the office add events with **Add event**; students are told about new module events.'],
                'sn' => ['Vhurai **Calendar**.', 'Inoratidza makirasi, zviitiko, mazuva eassignment nebvunzo.'],
                'nd' => ['Vula **Calendar**.', 'Itshengisa amakilasi, imicimbi, izinsuku ze-assignment lohlolo.'],
            ],
        ],
        [
            'id' => 'password', 'roles' => ['student', 'lecturer', 'admin'],
            'keywords' => ['password', 'forgot', 'login', 'log in', 'locked', 'reset', 'change password', 'pasiwedhi', 'password yangu', 'iphasiwedi'],
            'title' => ['en' => 'Forgot or want to change your password', 'sn' => 'Makakanganwa pasiwedhi', 'nd' => 'Ukhohlwe iphasiwedi'],
            'steps' => [
                'en' => ['Forgot it: on the login page tap **Forgot password?** and enter your email. The office resets it and sends you a new one on WhatsApp.', 'After 5 wrong tries the account locks for 15 minutes; wait, then try again.', 'To change it: open **My Profile** > **Change password**, type the old and the new password and save.'],
                'sn' => ['Kana makakanganwa: papeji yekupinda dzvanyai **Forgot password?**. Hofisi inokutumirai itsva paWhatsApp.', 'Kushandura: **My Profile** > **Change password**.'],
                'nd' => ['Nxa ukhohlwe: ekhasini lokungena cindezela **Forgot password?**. Ihhovisi likuthumela entsha ku-WhatsApp.', 'Ukuyitshintsha: **My Profile** > **Change password**.'],
            ],
        ],
    ];

    /** Short Bible study notes Ezra can lean on (and share when the model is offline). */
    public static $bible = [
        'romans 8' => "**Romans 8** is Paul's great chapter on life in the Spirit.\n- **v1-4:** there is no condemnation for those in Christ Jesus; the Spirit sets us free from the law of sin and death.\n- **v5-17:** the Spirit lives in believers, leads us and assures us we are God's children (\"Abba, Father\").\n- **v18-27:** present sufferings are small beside the coming glory; the Spirit helps us in our weakness and prays for us.\n- **v28-30:** God works all things together for good for those who love Him.\n- **v31-39:** nothing can separate us from the love of God in Christ Jesus.\nA good verse to memorise: Romans 8:28.",
        'john 3' => "**John 3** tells of Jesus and Nicodemus: to see the kingdom of God a person must be born again, of water and the Spirit (v3-8). John 3:16 sums up the gospel: God so loved the world that He gave His only Son, that whoever believes in Him should not perish but have everlasting life.",
        'psalm 23' => "**Psalm 23** is David's psalm of the Lord as Shepherd: He provides (v1-2), restores and guides (v3), is with us in the darkest valley (v4) and prepares a table and a home for us for ever (v5-6).",
        'ephesians 2' => "**Ephesians 2** shows we were dead in sin but made alive with Christ (v1-5). We are saved by grace through faith, not by works (v8-9), created for good works (v10), and Jew and Gentile are made one in Christ (v11-22).",
        'matthew 28' => "**Matthew 28:18-20**, the Great Commission: all authority is given to Jesus; go and make disciples of all nations, baptise them and teach them to obey; and He is with us always.",
        'acts 2' => "**Acts 2** records the day of Pentecost: the Holy Spirit is poured out and the believers speak in other tongues (v1-13), Peter preaches Christ (v14-36), about three thousand are saved and baptised (v37-41), and the church devotes itself to teaching, fellowship, breaking bread and prayer (v42-47).",
    ];

    /** Tips of the day for the student dashboard. */
    public static $tips = [
        'Hand in your assignment a day early: you can still replace it until the due date.',
        'Exams save your answers by themselves, even if your data drops. Just keep the page open.',
        'Open the **Calendar** each Monday to see the week\'s classes, due dates and exams.',
        'Read one chapter a day: Romans 8 is a wonderful place to start.',
        'Stuck on a reading? Ask in **Discussions**: your classmates probably have the same question.',
        'Upload a clear photo of your National ID under **My documents** so the office can verify you.',
        'Your lecturer\'s feedback is under **Assignments** > **Handed in**. Read it before the next task.',
        'Find commentaries and sermons for your module in the **Library**.',
        'Tap the bell to see your alerts: new materials, marks and events all show there.',
        '"Study to show yourself approved to God" (2 Timothy 2:15). A little every day beats a lot the night before.',
        'Add the portal to your home screen: in your browser menu choose **Add to Home screen**.',
        'Pray before you study: ask the Holy Spirit, our Helper, to open your understanding (John 14:26).',
        'Your attendance rate is on the **Attendance** page. Tell your lecturer before you miss a class.',
        'Keep your payment receipts: every approved payment has one under **Payments**.',
    ];

    /** Words that point to Shona or Ndebele (whole words, lower case). */
    protected static $langWords = [
        'sn' => ['ndinoda', 'ndingai', 'ndingaite', 'sei', 'ndapota', 'mhoro', 'mangwanani', 'masikati', 'manheru', 'maswera', 'makadii', 'ndiri', 'here', 'chii', 'rini', 'kupi', 'kuti', 'zvino', 'ndiudzei', 'ndibatsirei', 'ndibatsire', 'kubhadhara', 'bhadhara', 'mari', 'kosi', 'bvunzo', 'mamaki', 'chitupa', 'gwaro', 'magwaro', 'handizive', 'handigoni', 'ndaka', 'ndino', 'tinoda', 'nhasi', 'mangwana', 'zvakanaka', 'ndatenda', 'mwari', 'ishe', 'bhaibheri', 'rugwaro', 'kunyoresa', 'nyoresa', 'kuisa', 'kutumira'],
        'nd' => ['ngifuna', 'ngingakwenza', 'ngingenza', 'njani', 'ngiyacela', 'sawubona', 'salibonani', 'livukile', 'yini', 'nini', 'ngaphi', 'ukuthi', 'ngitshele', 'ngincedise', 'ncedisa', 'ukubhadala', 'bhadala', 'imali', 'isifundo', 'izifundo', 'uhlolo', 'amaphuzu', 'isitupa', 'amaphepha', 'angazi', 'angikwazi', 'ngiya', 'sifuna', 'lamuhla', 'kusasa', 'kulungile', 'ngiyabonga', 'unkulunkulu', 'inkosi', 'ibhayibhili', 'ukubhalisa', 'bhalisa', 'ukulayisha', 'ukuthumela', 'kanjani'],
    ];

    public static $languages = ['en' => 'English', 'sn' => 'Shona', 'nd' => 'Ndebele'];

    /* ----------------------------------------------------------- */

    /** 'en', 'sn' or 'nd', from the words in the message. */
    public function detect_language($text)
    {
        $words = preg_split('/[^a-z\']+/', mb_strtolower((string) $text), -1, PREG_SPLIT_NO_EMPTY);
        $score = ['sn' => 0, 'nd' => 0];
        foreach ($words as $w) {
            foreach (self::$langWords as $lang => $list) {
                if (in_array($w, $list, true)) {
                    $score[$lang] += 2;
                }
            }
            // Common word beginnings: Ndebele ngi-/uku-/aba-, Shona ndi-/ndo-/zvi-/ku-.
            if (preg_match('/^(ngi|uku|aba|izi|ama|nga)/', $w)) { $score['nd']++; }
            if (preg_match('/^(ndi|ndo|zvi|ndaka|ndino|mu[a-z]{4,})/', $w)) { $score['sn']++; }
        }
        arsort($score);
        $best = key($score);
        // One stray match isn't enough to switch language.
        return $score[$best] >= 3 ? $best : 'en';
    }

    /** 'student', 'lecturer' or 'admin' (from the session, as set at login). */
    public function role($role)
    {
        return in_array($role, ['admin', 'lecturer', 'student'], true) ? $role : 'student';
    }

    /**
     * The guides that best match a question, for this role, best first.
     * Each comes back with a 'score'.
     */
    public function find_guides($question, $role, $limit = 3)
    {
        $q = ' ' . preg_replace('/[^a-z0-9\' ]+/', ' ', mb_strtolower((string) $question)) . ' ';
        $found = [];
        foreach (self::$guides as $i => $g) {
            if (! in_array($role, $g['roles'], true)) {
                continue;
            }
            $score = 0;
            foreach ($g['keywords'] as $k) {
                if (strpos($q, ' ' . $k . ' ') !== false || (mb_strlen($k) >= 5 && strpos($q, $k) !== false)) {
                    $score += strpos($k, ' ') !== false ? 3 : 2;   // phrases count more
                }
            }
            if ($score > 0) {
                $found[] = $g + ['score' => $score, 'order' => $i];
            }
        }
        usort($found, function ($a, $b) { return $b['score'] - $a['score'] ?: $a['order'] - $b['order']; });
        return array_slice($found, 0, $limit);
    }

    /** A Bible passage note if the question names one we have ("Romans 8"). */
    public function bible_note($question)
    {
        $q = mb_strtolower((string) $question);
        foreach (self::$bible as $ref => $note) {
            if (preg_match('/\b' . preg_quote($ref, '/') . '\b/', $q)) {
                return $note;
            }
        }
        return null;
    }

    /** One guide as numbered steps, in the language asked (falls back to English). */
    public function guide_text(array $g, $lang = 'en')
    {
        $l = isset($g['steps'][$lang]) ? $lang : 'en';
        $out = '**' . $g['title'][$l] . "**\n";
        foreach ($g['steps'][$l] as $n => $step) {
            $out .= ($n + 1) . '. ' . $step . "\n";
        }
        return rtrim($out);
    }

    /**
     * The knowledge given to the model: matching guides (English, plus the
     * user's language), a Bible note if one fits, and a list of the other
     * guides by title so it knows what else it can explain.
     */
    public function context($question, $role, $lang)
    {
        $parts = [];
        $matched = $this->find_guides($question, $role);
        foreach ($matched as $g) {
            $parts[] = $this->guide_text($g, 'en') . ($lang !== 'en' ? "\n(" . self::$languages[$lang] . ")\n" . $this->guide_text($g, $lang) : '');
        }
        if ($note = $this->bible_note($question)) {
            $parts[] = "Bible study note:\n" . $note;
        }
        $others = [];
        foreach (self::$guides as $g) {
            if (in_array($role, $g['roles'], true) && ! in_array($g['id'], array_column($matched, 'id'), true)) {
                $others[] = $g['title']['en'];
            }
        }
        $parts[] = 'Other things you can explain for a ' . $role . ': ' . implode('; ', $others) . '.';
        return implode("\n\n", $parts);
    }

    /**
     * An answer without the AI model (it isn't running): the best matching
     * guide or Bible note, or a friendly list of what Ezra can help with.
     */
    public function fallback($question, $role, $lang)
    {
        $note = $this->bible_note($question);
        $matched = $this->find_guides($question, $role, 1);
        if ($matched) {
            return $this->guide_text($matched[0], $lang) . ($note ? "\n\n" . $note : '');
        }
        if ($note) {
            return $note;
        }
        $intro = [
            'en' => 'Sorry, I can\'t answer that fully right now (my AI helper isn\'t running), but I can explain how to do things in the portal. Try asking about:',
            'sn' => 'Ndine urombo, handikwanisi kupindura izvozvo izvozvi, asi ndinogona kukutsanangurirai maitiro ezvinhu muportal. Bvunzai nezve:',
            'nd' => 'Uxolo, angikwazi ukuphendula lokho khathesi, kodwa ngingakuchazela indlela yokwenza izinto ku-portal. Buza nge:',
        ];
        $list = [];
        foreach (self::$guides as $g) {
            if (in_array($role, $g['roles'], true)) {
                $list[] = '- ' . (isset($g['title'][$lang]) ? $g['title'][$lang] : $g['title']['en']);
            }
        }
        return $intro[$lang] . "\n" . implode("\n", $list);
    }

    /** Today's tip, picked at random from the date: the same all day, a different one tomorrow. */
    public function tip_of_the_day($date = null)
    {
        mt_srand(crc32($date ?: date('Y-m-d')));
        $tip = self::$tips[mt_rand(0, count(self::$tips) - 1)];
        mt_srand();   // don't leave the random generator predictable for other code
        return $tip;
    }
}
