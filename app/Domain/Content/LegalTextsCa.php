<?php

namespace App\Domain\Content;

/**
 * Catalan versions of the legal pages (Codi de consum de Catalunya, art. 128-1: consumers in Catalonia
 * have the right to receive the contractual information in Catalan). Same content and placeholders as
 * LegalTexts (Spanish, which prevails in case of doubt). Not legal advice: have them reviewed.
 */
final class LegalTextsCa
{
    /** @return array<string, array{title: string, body: string}> Spanish slug => Catalan page */
    public static function pages(): array
    {
        return [
            'aviso-legal' => ['title' => 'Avís legal', 'body' => self::legalNotice()],
            'politica-de-privacidad' => ['title' => 'Política de privacitat', 'body' => self::privacy()],
            'politica-de-cookies' => ['title' => 'Política de galetes', 'body' => self::cookies()],
            'condiciones-generales' => ['title' => 'Condicions generals de contractació', 'body' => self::terms()],
            'envios-y-devoluciones' => ['title' => 'Enviaments, recollida i devolucions', 'body' => self::shippingAndReturns()],
        ];
    }

    public static function legalNotice(): string
    {
        return <<<'HTML'
<h2>1. Dades identificatives</h2>
<p>En compliment de l'article 10 de la Llei 34/2002, d'11 de juliol, de serveis de la societat de la informació i de comerç electrònic (LSSI-CE), t'informem que el lloc web [web] (d'ara endavant, «el web») és titularitat de:</p>
<ul>
<li><strong>Titular:</strong> [nombre_legal]</li>
<li><strong>Nom comercial:</strong> [nombre_tienda]</li>
<li><strong>NIF:</strong> [nif]</li>
<li><strong>Domicili i botiga física:</strong> [direccion]</li>
<li><strong>Correu electrònic:</strong> [email]</li>
<li><strong>Telèfon i WhatsApp:</strong> [telefono]</li>
<li><strong>Dades registrals:</strong> [registro_mercantil]</li>
</ul>
<h2>2. Objecte</h2>
<p>El web ofereix informació sobre la botiga física [nombre_tienda] i permet comprar-ne els productes en línia. La compra es regeix per les <a href="/pagina/condiciones-generales">Condicions generals de contractació</a>.</p>
<h2>3. Condicions d'ús</h2>
<p>L'accés al web és gratuït i implica l'acceptació d'aquest avís legal. Et comprometes a fer servir el web i els seus continguts de manera lícita, sense perjudicar-ne el funcionament ni els drets de tercers, i a facilitar dades veritables quan et registris o facis una comanda.</p>
<h2>4. Propietat intel·lectual i industrial</h2>
<p>Els textos, fotografies, logotips, marques, disseny i codi del web pertanyen a [nombre_legal] o als seus llicenciants. Les marques dels productes pertanyen als seus fabricants. No es poden reproduir, distribuir ni transformar sense autorització, llevat del teu ús personal i privat.</p>
<h2>5. Responsabilitat</h2>
<p>Revisem amb cura la informació del web, però hi pot haver errors puntuals, que corregirem tan aviat com els detectem. No responem dels danys causats per un ús indegut del web ni d'interrupcions degudes a causes alienes al nostre control. Els enllaços a altres webs (per exemple, Google Maps o WhatsApp) s'ofereixen per comoditat; no som responsables del seu contingut.</p>
<h2>6. Protecció de dades i galetes</h2>
<p>El tractament de les teves dades personals s'explica a la <a href="/pagina/politica-de-privacidad">Política de privacitat</a> i l'ús de galetes, a la <a href="/pagina/politica-de-cookies">Política de galetes</a>.</p>
<h2>7. Legislació aplicable</h2>
<p>Aquest avís legal es regeix per la legislació espanyola. Per a qualsevol controvèrsia, si ets consumidor, seran competents els jutjats i tribunals del teu domicili.</p>
HTML;
    }

    public static function privacy(): string
    {
        return <<<'HTML'
<p>A [nombre_tienda] tractem les teves dades personals d'acord amb el Reglament (UE) 2016/679 (RGPD) i la Llei orgànica 3/2018 de protecció de dades personals i garantia dels drets digitals (LOPDGDD). Aquí t'expliquem quines dades fem servir, per a què i quins drets tens.</p>
<h2>1. Responsable del tractament</h2>
<ul>
<li><strong>Responsable:</strong> [nombre_legal] ([nombre_tienda]), NIF [nif]</li>
<li><strong>Adreça:</strong> [direccion]</li>
<li><strong>Contacte per a privacitat:</strong> [email] · [telefono]</li>
</ul>
<h2>2. Quines dades tractem</h2>
<ul>
<li><strong>Dades de contacte i identificació:</strong> nom, cognoms, correu electrònic, telèfon i, si ens el demanes per a una factura completa, NIF/DNI i adreça.</li>
<li><strong>Dades de les teves comandes:</strong> productes, imports, forma de lliurament, adreça de lliurament, codi de recollida, estat de la comanda i factures.</li>
<li><strong>Dades de pagament:</strong> no veiem ni desem les dades de la teva targeta ni del teu compte. L'entitat de pagament només ens comunica el resultat del pagament, un codi d'autorització i, si escau, el tipus de targeta.</li>
<li><strong>Dades del teu compte</strong> (si et registres): contrasenya xifrada, adreces desades i preferències.</li>
<li><strong>Converses d'atenció al client</strong> per correu electrònic o WhatsApp.</li>
<li><strong>Dades tècniques:</strong> adreça IP i registres de seguretat necessaris per protegir el web.</li>
</ul>
<p>No tractem dades de categories especials ni prenem decisions automatitzades amb efectes jurídics sobre tu.</p>
<h2>3. Per a què les fem servir i amb quina base legal</h2>
<ul>
<li><strong>Gestionar les teves comandes</strong> (preparació, avís que està a punt, recollida o enviament, pagament, factures, canvis i devolucions). Base legal: execució del contracte de compra (art. 6.1.b RGPD).</li>
<li><strong>Gestionar el teu compte de client</strong>, si decideixes crear-lo. Base legal: execució del contracte.</li>
<li><strong>Atendre't</strong> quan ens escrius per correu electrònic o WhatsApp. Base legal: la teva sol·licitud i, si té a veure amb una comanda, l'execució del contracte.</li>
<li><strong>Enviar-te novetats i ofertes</strong> per correu electrònic, només si ho has acceptat expressament. Pots retirar el consentiment en qualsevol moment des de «El meu compte» o des de l'enllaç de cada correu. Base legal: consentiment (art. 6.1.a RGPD i art. 21 LSSI-CE).</li>
<li><strong>Complir obligacions legals</strong> fiscals, comptables i de consum. Base legal: obligació legal (art. 6.1.c RGPD).</li>
<li><strong>Protegir el web</strong> davant d'usos fraudulents o atacs. Base legal: interès legítim (art. 6.1.f RGPD).</li>
<li><strong>Mesurar l'ús del web i dels nostres anuncis</strong>, només si acceptes les galetes d'analítica o publicitat. Base legal: consentiment (art. 6.1.a RGPD i art. 22.2 LSSI-CE). Vegeu la <a href="/pagina/politica-de-cookies">Política de galetes</a>.</li>
</ul>
<h2>4. Atenció per WhatsApp i assistent automàtic</h2>
<p>Si ens escrius per WhatsApp al [telefono], els teus missatges, el teu número i el teu nom de perfil es tracten a través de WhatsApp Business, servei de Meta Platforms Ireland Ltd. Per respondre més ràpidament, els primers missatges els pot contestar un <strong>assistent automàtic basat en intel·ligència artificial</strong> (proveïdor: Anthropic), que només consulta informació de la botiga i de les teves comandes per respondre't. Sempre pots demanar que t'atengui una persona de l'equip. No facis servir WhatsApp per enviar-nos dades de targetes, contrasenyes ni documents d'identitat.</p>
<h2>5. Quant de temps conservem les dades</h2>
<ul>
<li><strong>Comandes i facturació:</strong> durant el termini de garantia i, després, el que exigeix la llei (6 anys per a la documentació mercantil, art. 30 del Codi de comerç, i 4 anys a efectes fiscals).</li>
<li><strong>Compte de client:</strong> mentre no l'eliminis. En eliminar-lo, anonimitzem les teves dades personals i només conservem el que la llei ens obliga a guardar.</li>
<li><strong>Comunicacions comercials:</strong> fins que retiris el consentiment.</li>
<li><strong>Converses d'atenció al client:</strong> fins a 2 anys des de l'última conversa, llevat que estiguin lligades a una comanda o a una reclamació.</li>
<li><strong>Registres de seguretat:</strong> com a màxim 1 any.</li>
</ul>
<h2>6. Amb qui compartim les dades</h2>
<p>No venem les teves dades. Només les comuniquem a tercers quan és necessari per donar-te el servei o per obligació legal:</p>
<ul>
<li><strong>Proveïdors que actuen per compte nostre</strong> (encarregats del tractament), amb contracte que els obliga a protegir les teves dades: allotjament web (Hostinger International Ltd., UE), enviament de correus electrònics, WhatsApp Business (Meta), assistent d'intel·ligència artificial (Anthropic) i empreses de transport.</li>
<li><strong>Entitats de pagament</strong>, que tracten les teves dades com a responsables per cobrar la comanda i prevenir el frau: Redsys Servicios de Procesamiento, S.L. i el teu banc (targeta i Bizum) i PayPal (Europe) S.à r.l. et Cie, S.C.A.</li>
<li><strong>Google i Meta</strong>, només si acceptes les galetes d'analítica o publicitat.</li>
<li><strong>Administracions públiques</strong>, com l'Agència Tributària, quan la llei ho exigeix.</li>
</ul>
<p>Alguns d'aquests proveïdors (Meta, Anthropic, Google i PayPal) poden tractar dades fora de l'Espai Econòmic Europeu, principalment als Estats Units. En aquests casos la transferència s'empara en el Marc de Privacitat de Dades UE-EUA o en les clàusules contractuals tipus aprovades per la Comissió Europea.</p>
<h2>7. Els teus drets</h2>
<p>Pots exercir en qualsevol moment els teus drets d'<strong>accés, rectificació, supressió, oposició, limitació del tractament i portabilitat</strong>, i retirar els consentiments que ens hagis donat, escrivint a [email] o a la botiga ([direccion]). Et demanarem que acreditis la teva identitat. Si tens compte, des de «El meu compte» pots descarregar les teves dades i eliminar el compte tu mateix.</p>
<p>Si creus que no hem atès bé la teva sol·licitud, pots presentar una reclamació davant l'<strong>Agència Espanyola de Protecció de Dades</strong> (www.aepd.es).</p>
<h2>8. Menors d'edat</h2>
<p>Per crear un compte o fer una comanda has de ser major de 18 anys. No tractem conscientment dades de menors de 14 anys sense el consentiment dels seus pares o tutors.</p>
<h2>9. Seguretat</h2>
<p>Apliquem mesures tècniques i organitzatives per protegir les teves dades: connexió xifrada (HTTPS), contrasenyes xifrades, verificació en dos passos per al personal, accés limitat segons les funcions i còpies de seguretat.</p>
<h2>10. Canvis</h2>
<p>Podem actualitzar aquesta política per adaptar-la a canvis legals o dels nostres serveis. La data de l'última actualització apareix al final d'aquesta pàgina.</p>
HTML;
    }

    public static function cookies(): string
    {
        return <<<'HTML'
<h2>1. Què són les galetes?</h2>
<p>Les galetes (cookies) són petits fitxers que un web desa al teu navegador per recordar informació, per exemple, que has iniciat la sessió o què hi ha a la teva cistella.</p>
<h2>2. Galetes que fem servir</h2>
<h3>Galetes tècniques (sempre actives)</h3>
<p>Són imprescindibles perquè el web funcioni. Segons l'article 22.2 de la LSSI-CE i la Guia sobre l'ús de les galetes de l'Agència Espanyola de Protecció de Dades, no necessiten el teu consentiment.</p>
<table>
<thead><tr><th>Galeta</th><th>Per a què serveix</th><th>Durada</th></tr></thead>
<tbody>
<tr><td>Galeta de sessió de [nombre_tienda]</td><td>Manté la teva sessió, la cistella, l'idioma triat i les dades de compra mentre navegues.</td><td>2 hores sense activitat</td></tr>
<tr><td>XSRF-TOKEN</td><td>Seguretat: protegeix els formularis davant d'enviaments fraudulents.</td><td>2 hores</td></tr>
<tr><td>remember_web_…</td><td>Només si marques «Recorda'm» en iniciar la sessió: manté la sessió oberta en aquest dispositiu.</td><td>Fins que tanquis la sessió (màxim 400 dies)</td></tr>
<tr><td>tm_consent</td><td>Desa la teva elecció sobre les galetes per no tornar-te a preguntar.</td><td>12 mesos</td></tr>
</tbody>
</table>
<p>En pagar amb targeta, Bizum o PayPal passes a la pàgina segura de l'entitat de pagament, que pot fer servir les seves pròpies galetes tècniques i antifrau sota la seva responsabilitat.</p>
<h3>Galetes d'analítica i publicitat (només amb el teu consentiment)</h3>
<p>Si en algun moment les activem, només es carreguen <strong>després que les acceptis</strong> a l'avís de galetes, que et permet acceptar-les, rebutjar-les o triar amb la mateixa facilitat. Mentre no decideixis, no s'instal·len.</p>
<table>
<thead><tr><th>Galeta</th><th>Titular</th><th>Per a què serveix</th><th>Durada</th></tr></thead>
<tbody>
<tr><td>_ga, _ga_…</td><td>Google Ireland Ltd. (Google Analytics)</td><td>Analítica: quantes persones visiten el web i quines pàgines veuen, de manera estadística.</td><td>Fins a 2 anys</td></tr>
<tr><td>_fbp</td><td>Meta Platforms Ireland Ltd. (píxel de Meta)</td><td>Publicitat: mesurar els nostres anuncis a Facebook i Instagram i mostrar-te ofertes.</td><td>3 mesos</td></tr>
</tbody>
</table>
<p>Pots canviar o retirar el consentiment quan vulguis des de l'enllaç «Configurar galetes» del peu de pàgina.</p>
<h2>3. Enllaços a altres serveis</h2>
<p>Els enllaços a Google Maps o WhatsApp obren aquests serveis en una altra pestanya o aplicació. Aquest web no els carrega per si mateix, de manera que no instal·len galetes fins que els obres. En fer-los servir s'apliquen les polítiques de Google i de Meta.</p>
<h2>4. Com eliminar o bloquejar les galetes</h2>
<p>Pots esborrar o bloquejar les galetes des de la configuració del teu navegador (Chrome, Firefox, Safari, Edge…). Tingues en compte que, si bloqueges les galetes tècniques, no podràs fer servir la cistella ni iniciar la sessió.</p>
<h2>5. Més informació</h2>
<p>Per a qualsevol dubte sobre aquesta política, escriu-nos a [email]. El tractament de les teves dades s'explica a la <a href="/pagina/politica-de-privacidad">Política de privacitat</a>.</p>
HTML;
    }

    public static function terms(): string
    {
        return <<<'HTML'
<p>Aquestes condicions regulen la compra de productes a [web] a [nombre_legal] ([nombre_tienda]). Si us plau, llegeix-les abans de fer la comanda: en confirmar-la declares que les acceptes. Si ets consumidor, res del que s'indica aquí limita els drets que et reconeix la llei.</p>
<h2>1. Venedor</h2>
<p>[nombre_legal], NIF [nif], amb domicili i botiga física a [direccion]. Contacte: [email] · [telefono] (també WhatsApp). Horari de la botiga: [horario].</p>
<h2>2. Qui pot comprar</h2>
<p>Persones majors de 18 anys amb capacitat per contractar. Venem a clients particulars i empreses a Espanya (Península i Balears). De moment no venem a Canàries, Ceuta ni Melilla.</p>
<h2>3. Productes i preus</h2>
<ul>
<li>Les fotos i descripcions intenten reflectir fidelment cada producte; hi pot haver petites diferències de color o mida.</li>
<li>Tots els preus són en euros i <strong>inclouen l'IVA</strong>. Abans de confirmar la comanda veuràs l'import total, incloses, si escau, les despeses d'enviament.</li>
<li>El preu aplicable és el que apareix en el moment de confirmar la comanda. Si per un error evident un preu fos clarament desproporcionat, t'avisarem i podràs confirmar la compra al preu correcte o cancel·lar-la sense cost.</li>
<li>La disponibilitat mostrada correspon a l'estoc de la botiga en aquell moment. Si un producte s'esgotés després de la teva comanda, t'avisarem i et reemborsarem el que n'haguessis pagat.</li>
</ul>
<h2>4. Com comprar</h2>
<ol>
<li>Afegeix els productes a la cistella.</li>
<li>Tria la forma de lliurament, escriu les teves dades de contacte i tria la forma de pagament.</li>
<li>Revisa el resum, accepta aquestes condicions i prem el botó de confirmar la comanda. Aquest botó implica una <strong>obligació de pagament</strong>.</li>
<li>T'enviarem per correu electrònic la confirmació amb el número de comanda i, si recculls a la botiga, el teu codi de recollida. També pots consultar la comanda des de l'enllaç del correu o des de «El meu compte».</li>
</ol>
<p>El contracte es formalitza en castellà o en català, segons l'idioma que triïs al web (també pots navegar en anglès). Desem una còpia electrònica de la comanda, a la qual pots accedir des del correu de confirmació o des de «El meu compte». Si detectes un error en les teves dades abans de confirmar, el pots corregir al mateix formulari; després, escriu-nos com més aviat millor.</p>
<h2>5. Formes de lliurament</h2>
<ul>
<li><strong>Recollida a la botiga (Click &amp; Collect), gratuïta:</strong> preparem la teva comanda i t'avisem per correu electrònic quan estigui a punt. La pots recollir a [direccion] (horari: [horario]) mostrant el teu codi de recollida (o el codi QR del correu) i un document d'identitat. Una altra persona la pot recollir en nom teu amb el codi de la comanda. Guardem la comanda <strong>15 dies naturals</strong> des de l'avís; passat aquest termini sense recollir-la, la podrem cancel·lar i, si ja estava pagada, et retornarem l'import.</li>
<li><strong>Enviament a domicili</strong> a la Península i Balears, amb pagament en línia. El preu depèn de la zona (i, si escau, del pes del paquet) i es mostra a la cistella abans de confirmar la comanda, juntament amb l'import a partir del qual l'enviament és gratuït. El termini habitual és de 24–48 hores laborables des que la comanda surt de la botiga; t'enviarem el número de seguiment per correu electrònic.</li>
<li><strong>Lliurament local</strong>, als codis postals on repartim nosaltres mateixos, amb el preu i el termini indicats a la cistella.</li>
</ul>
<h2>6. Formes de pagament</h2>
<ul>
<li><strong>Pagament a la botiga en recollir la comanda</strong>, pels mitjans acceptats a la botiga.</li>
<li><strong>Pagament en línia</strong> amb targeta (amb autenticació segura 3-D Secure) o Bizum a través de Redsys, o amb PayPal. Pagues a la pàgina segura de l'entitat de pagament; nosaltres no veiem ni desem les dades de la teva targeta. Mentre pagues et guardem els productes uns minuts; si el pagament no es completa en aquest temps, la comanda es cancel·la sense cap càrrec.</li>
</ul>
<p>La comanda queda confirmada quan l'entitat de pagament ens confirma el cobrament (o, si pagues a la botiga, en recollir-la). T'enviarem la <strong>factura</strong> en format electrònic (PDF), que també pots descarregar des de la comanda o des de «El meu compte». Si indiques el teu NIF en fer la comanda, emetrem una factura completa a nom teu; en cas contrari, una factura simplificada. Si et retornem diners, emetrem la factura rectificativa corresponent.</p>
<h2>7. Dret de desistiment</h2>
<p>Si ets consumidor, tens dret a desistir de la compra en un termini de <strong>14 dies naturals</strong> sense necessitat de justificar-ho, d'acord amb els articles 102 i següents del Reial decret legislatiu 1/2007 (Llei general per a la defensa dels consumidors i usuaris) i el Codi de consum de Catalunya. El termini comença el dia que tu, o la persona que indiquis, recculls o reps el producte. Com fer-ho, terminis de reemborsament, excepcions i el formulari són a <a href="/pagina/envios-y-devoluciones">Enviaments, recollida i devolucions</a>.</p>
<h2>8. Garantia</h2>
<p>Tots els productes nous tenen la <strong>garantia legal de conformitat de 3 anys</strong> des del lliurament (articles 114 i següents de la Llei general per a la defensa dels consumidors i usuaris). Si un producte no és conforme, pots triar entre la reparació o la substitució, sense cost per a tu; si no fos possible o no es fes en un termini raonable, podràs demanar una rebaixa del preu o la devolució de l'import. Per fer servir la garantia, porta'l a la botiga o escriu-nos a [email] amb el número de comanda. Alguns fabricants ofereixen a més una garantia comercial, que no substitueix la legal.</p>
<h2>9. Atenció al client i reclamacions</h2>
<p>Ens pots contactar a [email], al [telefono] (també per WhatsApp) o a la botiga. Respondrem les reclamacions en el termini més breu possible i, com a màxim, en un mes. Tens a la teva disposició fulls oficials de queixa, reclamació i denúncia a la botiga i al portal de l'Agència Catalana del Consum (consum.gencat.cat). També pots acudir a la Junta Arbitral de Consum de Catalunya o als serveis de consum del teu municipi.</p>
<h2>10. Llei aplicable i jurisdicció</h2>
<p>Aquestes condicions es regeixen per la legislació espanyola. Si ets consumidor, per a qualsevol controvèrsia seran competents els jutjats i tribunals del teu domicili.</p>
HTML;
    }

    public static function shippingAndReturns(): string
    {
        return <<<'HTML'
<h2>1. Recollida a la botiga (Click &amp; Collect)</h2>
<ul>
<li><strong>Gratuïta.</strong> Fes la comanda en línia i recull-la a [direccion].</li>
<li>T'avisem per correu electrònic tan bon punt està preparada. Horari de recollida: [horario].</li>
<li>Mostra el codi de recollida (o el codi QR del correu) i un document d'identitat. Una altra persona la pot recollir per tu amb el codi de la comanda.</li>
<li>Guardem la teva comanda <strong>15 dies naturals</strong> des de l'avís que està a punt.</li>
</ul>
<h2>2. Enviament a domicili</h2>
<ul>
<li>Enviem a la <strong>Península i Balears</strong>. De moment no enviem a Canàries, Ceuta ni Melilla.</li>
<li>El preu depèn de la zona (i, si escau, del pes del paquet). El veuràs a la cistella en escriure el teu codi postal, abans de confirmar la comanda, juntament amb l'import a partir del qual l'enviament és gratuït.</li>
<li>Als codis postals on repartim nosaltres mateixos oferim <strong>lliurament local</strong>, amb el seu propi preu i termini.</li>
<li>Termini habitual: 24–48 hores laborables des que la comanda surt de la botiga. T'enviarem per correu electrònic el número de seguiment.</li>
<li>Si no ets a casa, l'empresa de transport deixarà un avís o intentarà un nou lliurament. Si el paquet arriba malmès, indica-ho al transportista i avisa'ns com més aviat millor.</li>
</ul>
<h2>3. Devolucions: dret de desistiment (14 dies)</h2>
<p>Si ets consumidor, pots retornar la teva compra en línia en un termini de <strong>14 dies naturals</strong> des que recculls o reps el producte, sense indicar-ne el motiu. Si tens compte, pots demanar la devolució o el canvi des de «El meu compte».</p>
<h3>Com retornar un producte</h3>
<ol>
<li><strong>Comunica'ns la teva decisió</strong> dins del termini: escriu-nos a [email], vine a la botiga o envia'ns el formulari de sota (no és obligatori fer-lo servir). Indica el número de comanda.</li>
<li><strong>Retorna'ns el producte</strong> en un termini màxim de 14 dies naturals des que ens comuniques la decisió. La manera més fàcil és portar-lo a la botiga, sense cost. Si prefereixes enviar-lo, el cost de l'enviament de tornada va a càrrec teu.</li>
<li>Retorna'l en bon estat, amb els accessoris i, si pot ser, amb l'embalatge. Només respondràs de la pèrdua de valor deguda a una manipulació diferent de la necessària per comprovar-ne la naturalesa, les característiques i el funcionament.</li>
</ol>
<h3>Reemborsament</h3>
<p>Et retornarem tot el que hagis pagat pel producte, incloses les despeses d'enviament inicials estàndard si n'hi va haver, en un termini màxim de <strong>14 dies naturals</strong> des que ens comuniquis el desistiment, amb el mateix mitjà de pagament que vas fer servir (llevat que n'acordem un altre, sense cost per a tu). Podem esperar a tenir el producte, o que ens demostris que l'has enviat, abans de reemborsar. Si vas pagar a la botiga en efectiu, et reemborsarem en efectiu o per transferència.</p>
<h3>Productes que no es poden retornar per desistiment</h3>
<ul>
<li>Productes fabricats o personalitzats segons les teves indicacions.</li>
<li>Productes precintats que, per raons d'higiene o de protecció de la salut, s'hagin desprecintat després del lliurament (per exemple, cosmètics o articles d'higiene personal).</li>
<li>Productes que es puguin deteriorar o caducar ràpidament.</li>
<li>Enregistraments de so o vídeo i programes informàtics precintats que hagis desprecintat.</li>
</ul>
<h2>4. Productes defectuosos o equivocats</h2>
<p>Si un producte arriba defectuós, no funciona o no és el que vas demanar, avisa'ns com més aviat millor a [email], per WhatsApp al [telefono] o a la botiga. El repararem o el canviarem sense cost, o et retornarem els diners, segons la <strong>garantia legal de 3 anys</strong> (vegeu les <a href="/pagina/condiciones-generales">Condicions generals</a>). En aquests casos no pagues cap despesa de devolució.</p>
<h2>5. Compres fetes a la botiga física</h2>
<p>El dret de desistiment de 14 dies s'aplica a les compres fetes al web. Les compres fetes directament a la botiga física es regeixen per la política de canvis de la botiga, de la qual t'informarem a la caixa, i sempre per la garantia legal.</p>
<h2>6. Model de formulari de desistiment</h2>
<p><em>(Només s'ha d'emplenar i enviar aquest formulari si vol desistir del contracte).</em></p>
<ul>
<li>A l'atenció de [nombre_legal] ([nombre_tienda]), [direccion], correu electrònic [email]:</li>
<li>Per la present li comunico/comuniquem (*) que desisteixo/desistim (*) del meu/nostre (*) contracte de venda del bé/prestació del servei (*) següent:</li>
<li>Demanat el/rebut el (*):</li>
<li>Número de comanda:</li>
<li>Nom del consumidor o dels consumidors:</li>
<li>Domicili del consumidor o dels consumidors:</li>
<li>Signatura del consumidor o dels consumidors (només si aquest formulari es presenta en paper):</li>
<li>Data:</li>
</ul>
<p>(*) Ratlleu el que no correspongui.</p>
HTML;
    }
}
