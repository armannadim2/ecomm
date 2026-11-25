<?php

namespace App\Domain\Content;

/**
 * Texts of the legal pages (Spanish), written for how Tot & Més sells today:
 * physical shop + online shop with Click & Collect paid in the shop; home delivery and online
 * payment described as "when available"; only technical cookies; WhatsApp (with an AI assistant)
 * for customer service.
 *
 * Placeholders are filled from the shop settings when the page is shown (Page::renderedBody):
 * [nombre_tienda] [nombre_legal] [nif] [direccion] [email] [telefono] [registro_mercantil] [web] [horario].
 *
 * Seeded by LegalPagesSeeder (new installs) and rewritten by `php artisan legal:refresh`.
 * Not legal advice: the owner should have them reviewed, and keep them in line with real practice.
 */
final class LegalTexts
{
    /** @return array<string, array{title: string, body: string}> slug => page */
    public static function pages(): array
    {
        return [
            'aviso-legal' => ['title' => 'Aviso legal', 'body' => self::legalNotice()],
            'politica-de-privacidad' => ['title' => 'Política de privacidad', 'body' => self::privacy()],
            'politica-de-cookies' => ['title' => 'Política de cookies', 'body' => self::cookies()],
            'condiciones-generales' => ['title' => 'Condiciones generales de contratación', 'body' => self::terms()],
            'envios-y-devoluciones' => ['title' => 'Envíos, recogida y devoluciones', 'body' => self::shippingAndReturns()],
        ];
    }

    public static function legalNotice(): string
    {
        return <<<'HTML'
<h2>1. Datos identificativos</h2>
<p>En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE), te informamos de que el sitio web [web] (en adelante, «la web») es titularidad de:</p>
<ul>
<li><strong>Titular:</strong> [nombre_legal]</li>
<li><strong>Nombre comercial:</strong> [nombre_tienda]</li>
<li><strong>NIF:</strong> [nif]</li>
<li><strong>Domicilio y tienda física:</strong> [direccion]</li>
<li><strong>Correo electrónico:</strong> [email]</li>
<li><strong>Teléfono y WhatsApp:</strong> [telefono]</li>
<li><strong>Datos registrales:</strong> [registro_mercantil]</li>
</ul>
<h2>2. Objeto</h2>
<p>La web ofrece información sobre la tienda física [nombre_tienda] y permite comprar sus productos en línea. La compra se rige por las <a href="/pagina/condiciones-generales">Condiciones generales de contratación</a>.</p>
<h2>3. Condiciones de uso</h2>
<p>El acceso a la web es gratuito y supone la aceptación de este aviso legal. Te comprometes a usar la web y sus contenidos de forma lícita, sin dañar su funcionamiento ni los derechos de terceros, y a facilitar datos verdaderos cuando te registres o hagas un pedido.</p>
<h2>4. Propiedad intelectual e industrial</h2>
<p>Los textos, fotografías, logotipos, marcas, diseño y código de la web pertenecen a [nombre_legal] o a sus licenciantes. Las marcas de los productos pertenecen a sus fabricantes. No está permitido reproducirlos, distribuirlos ni transformarlos sin autorización, salvo para tu uso personal y privado.</p>
<h2>5. Responsabilidad</h2>
<p>Revisamos con cuidado la información de la web, pero pueden existir errores puntuales, que corregiremos en cuanto los detectemos. No respondemos de los daños causados por un uso indebido de la web ni de interrupciones debidas a causas ajenas a nuestro control. Los enlaces a otras webs (por ejemplo, Google Maps o WhatsApp) se ofrecen por comodidad; no somos responsables de su contenido.</p>
<h2>6. Protección de datos y cookies</h2>
<p>El tratamiento de tus datos personales se explica en la <a href="/pagina/politica-de-privacidad">Política de privacidad</a> y el uso de cookies en la <a href="/pagina/politica-de-cookies">Política de cookies</a>.</p>
<h2>7. Legislación aplicable</h2>
<p>Este aviso legal se rige por la legislación española. Para cualquier controversia, si eres consumidor, serán competentes los juzgados y tribunales de tu domicilio.</p>
HTML;
    }

    public static function privacy(): string
    {
        return <<<'HTML'
<p>En [nombre_tienda] tratamos tus datos personales de acuerdo con el Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos Personales y garantía de los derechos digitales (LOPDGDD). Aquí te explicamos qué datos usamos, para qué y qué derechos tienes.</p>
<h2>1. Responsable del tratamiento</h2>
<ul>
<li><strong>Responsable:</strong> [nombre_legal] ([nombre_tienda]), NIF [nif]</li>
<li><strong>Dirección:</strong> [direccion]</li>
<li><strong>Contacto para privacidad:</strong> [email] · [telefono]</li>
</ul>
<h2>2. Qué datos tratamos</h2>
<ul>
<li><strong>Datos de contacto e identificación:</strong> nombre, apellidos, correo electrónico, teléfono y, si nos lo pides para una factura completa, NIF/DNI y dirección.</li>
<li><strong>Datos de tus pedidos:</strong> productos, importes, forma de entrega, código de recogida y estado del pedido.</li>
<li><strong>Datos de tu cuenta</strong> (si te registras): contraseña cifrada, direcciones guardadas y preferencias.</li>
<li><strong>Conversaciones de atención al cliente</strong> por correo electrónico o WhatsApp.</li>
<li><strong>Datos técnicos:</strong> dirección IP y registros de seguridad necesarios para proteger la web.</li>
</ul>
<p>No tratamos datos de categorías especiales ni tomamos decisiones automatizadas con efectos jurídicos sobre ti.</p>
<h2>3. Para qué los usamos y con qué base legal</h2>
<ul>
<li><strong>Gestionar tus pedidos</strong> (preparación, aviso de que está listo, recogida, pago, cambios y devoluciones). Base legal: ejecución del contrato de compra (art. 6.1.b RGPD).</li>
<li><strong>Gestionar tu cuenta de cliente</strong>, si decides crearla. Base legal: ejecución del contrato.</li>
<li><strong>Atenderte</strong> cuando nos escribes por correo electrónico o WhatsApp. Base legal: tu solicitud y, si tiene que ver con un pedido, la ejecución del contrato.</li>
<li><strong>Enviarte novedades y ofertas</strong> por correo electrónico, solo si lo has aceptado expresamente. Puedes retirar tu consentimiento en cualquier momento desde «Mi cuenta» o desde el enlace de cada correo. Base legal: consentimiento (art. 6.1.a RGPD y art. 21 LSSI-CE).</li>
<li><strong>Cumplir obligaciones legales</strong> fiscales, contables y de consumo. Base legal: obligación legal (art. 6.1.c RGPD).</li>
<li><strong>Proteger la web</strong> frente a usos fraudulentos o ataques. Base legal: interés legítimo (art. 6.1.f RGPD).</li>
</ul>
<h2>4. Atención por WhatsApp y asistente automático</h2>
<p>Si nos escribes por WhatsApp al [telefono], tus mensajes, tu número y tu nombre de perfil se tratan a través de WhatsApp Business, servicio de Meta Platforms Ireland Ltd. Para responder más rápido, los primeros mensajes pueden ser contestados por un <strong>asistente automático basado en inteligencia artificial</strong> (proveedor: Anthropic), que solo consulta información de la tienda y de tus pedidos para responderte. Siempre puedes pedir que te atienda una persona del equipo. No uses WhatsApp para enviarnos datos de tarjetas, contraseñas ni documentos de identidad.</p>
<h2>5. Cuánto tiempo conservamos los datos</h2>
<ul>
<li><strong>Pedidos y facturación:</strong> durante el plazo de garantía y, después, el exigido por la ley (6 años para la documentación mercantil, art. 30 del Código de Comercio, y 4 años a efectos fiscales).</li>
<li><strong>Cuenta de cliente:</strong> mientras no la elimines. Al eliminarla, anonimizamos tus datos personales y conservamos solo lo que la ley nos obliga a guardar.</li>
<li><strong>Comunicaciones comerciales:</strong> hasta que retires tu consentimiento.</li>
<li><strong>Conversaciones de atención al cliente:</strong> hasta 2 años desde la última conversación, salvo que estén ligadas a un pedido o a una reclamación.</li>
<li><strong>Registros de seguridad:</strong> como máximo 1 año.</li>
</ul>
<h2>6. Con quién compartimos los datos</h2>
<p>No vendemos tus datos. Solo los comunicamos a terceros cuando es necesario para darte el servicio o por obligación legal:</p>
<ul>
<li><strong>Proveedores que actúan por nuestra cuenta</strong> (encargados del tratamiento), con contrato que les obliga a proteger tus datos: alojamiento web (Hostinger International Ltd., UE), envío de correos electrónicos, WhatsApp Business (Meta), asistente de inteligencia artificial (Anthropic) y, cuando estén disponibles, entidades de pago (Redsys, PayPal) y empresas de transporte.</li>
<li><strong>Administraciones públicas</strong>, como la Agencia Tributaria, cuando la ley lo exige.</li>
</ul>
<p>Algunos de estos proveedores (Meta y Anthropic) pueden tratar datos fuera del Espacio Económico Europeo, principalmente en Estados Unidos. En esos casos la transferencia se ampara en el Marco de Privacidad de Datos UE-EE. UU. o en las cláusulas contractuales tipo aprobadas por la Comisión Europea.</p>
<h2>7. Tus derechos</h2>
<p>Puedes ejercer en cualquier momento tus derechos de <strong>acceso, rectificación, supresión, oposición, limitación del tratamiento y portabilidad</strong>, y retirar los consentimientos que nos hayas dado, escribiendo a [email] o en la tienda ([direccion]). Te pediremos que acredites tu identidad. Si tienes cuenta, desde «Mi cuenta» puedes descargar tus datos y eliminar tu cuenta tú mismo.</p>
<p>Si crees que no hemos atendido bien tu solicitud, puedes presentar una reclamación ante la <strong>Agencia Española de Protección de Datos</strong> (www.aepd.es).</p>
<h2>8. Menores de edad</h2>
<p>Para crear una cuenta o hacer un pedido debes ser mayor de 18 años. No tratamos conscientemente datos de menores de 14 años sin el consentimiento de sus padres o tutores.</p>
<h2>9. Seguridad</h2>
<p>Aplicamos medidas técnicas y organizativas para proteger tus datos: conexión cifrada (HTTPS), contraseñas cifradas, verificación en dos pasos para el personal, acceso limitado según funciones y copias de seguridad.</p>
<h2>10. Cambios</h2>
<p>Podemos actualizar esta política para adaptarla a cambios legales o en nuestros servicios. La fecha de la última actualización aparece al final de esta página.</p>
HTML;
    }

    public static function cookies(): string
    {
        return <<<'HTML'
<h2>1. ¿Qué son las cookies?</h2>
<p>Las cookies son pequeños archivos que una web guarda en tu navegador para recordar información, por ejemplo, que has iniciado sesión o lo que hay en tu cesta.</p>
<h2>2. Cookies que usamos</h2>
<p>Esta web usa <strong>solo cookies técnicas</strong>, imprescindibles para que funcione. Según el artículo 22.2 de la LSSI-CE y la Guía sobre el uso de las cookies de la Agencia Española de Protección de Datos, estas cookies no necesitan tu consentimiento.</p>
<table>
<thead><tr><th>Cookie</th><th>Para qué sirve</th><th>Duración</th></tr></thead>
<tbody>
<tr><td>Cookie de sesión de [nombre_tienda]</td><td>Mantiene tu sesión, tu cesta, el idioma elegido y tus datos de compra mientras navegas.</td><td>2 horas sin actividad</td></tr>
<tr><td>XSRF-TOKEN</td><td>Seguridad: protege los formularios frente a envíos fraudulentos.</td><td>2 horas</td></tr>
<tr><td>remember_web_…</td><td>Solo si marcas «Recordarme» al iniciar sesión: mantiene tu sesión abierta en ese dispositivo.</td><td>Hasta que cierres sesión (máximo 400 días)</td></tr>
</tbody>
</table>
<p>Son cookies propias: no las compartimos con terceros. <strong>No usamos cookies de análisis, publicidad ni redes sociales.</strong> Si en el futuro las añadimos, te pediremos antes tu consentimiento con un aviso que te permitirá aceptarlas o rechazarlas con la misma facilidad.</p>
<h2>3. Enlaces a otros servicios</h2>
<p>Los enlaces a Google Maps o WhatsApp abren esos servicios en otra pestaña o aplicación. Esta web no los carga por sí misma, por lo que no instalan cookies hasta que los abres. Al usarlos se aplican las políticas de Google y de Meta.</p>
<h2>4. Cómo eliminar o bloquear las cookies</h2>
<p>Puedes borrar o bloquear las cookies desde la configuración de tu navegador (Chrome, Firefox, Safari, Edge…). Ten en cuenta que, si bloqueas las cookies técnicas, no podrás usar la cesta ni iniciar sesión.</p>
<h2>5. Más información</h2>
<p>Para cualquier duda sobre esta política, escríbenos a [email]. El tratamiento de tus datos se explica en la <a href="/pagina/politica-de-privacidad">Política de privacidad</a>.</p>
HTML;
    }

    public static function terms(): string
    {
        return <<<'HTML'
<p>Estas condiciones regulan la compra de productos en [web] a [nombre_legal] ([nombre_tienda]). Por favor, léelas antes de hacer tu pedido: al confirmarlo declaras que las aceptas. Si eres consumidor, nada de lo aquí indicado limita los derechos que te reconoce la ley.</p>
<h2>1. Vendedor</h2>
<p>[nombre_legal], NIF [nif], con domicilio y tienda física en [direccion]. Contacto: [email] · [telefono] (también WhatsApp). Horario de la tienda: [horario].</p>
<h2>2. Quién puede comprar</h2>
<p>Personas mayores de 18 años con capacidad para contratar. Vendemos a clientes particulares y empresas en España (Península y Baleares). De momento no vendemos a Canarias, Ceuta ni Melilla.</p>
<h2>3. Productos y precios</h2>
<ul>
<li>Las fotos y descripciones intentan reflejar fielmente cada producto; puede haber pequeñas diferencias de color o tamaño.</li>
<li>Todos los precios están en euros e <strong>incluyen el IVA</strong>. Antes de confirmar el pedido verás el importe total, incluidos, en su caso, los gastos de envío.</li>
<li>El precio aplicable es el que aparece en el momento de confirmar el pedido. Si por un error evidente un precio fuera claramente desproporcionado, te avisaremos y podrás confirmar la compra al precio correcto o cancelarla sin coste.</li>
<li>La disponibilidad mostrada corresponde al stock de la tienda en ese momento. Si un producto se agotara después de tu pedido, te avisaremos y te reembolsaremos lo que hubieras pagado por él.</li>
</ul>
<h2>4. Cómo comprar</h2>
<ol>
<li>Añade los productos a la cesta.</li>
<li>Elige la forma de entrega, escribe tus datos de contacto y elige la forma de pago.</li>
<li>Revisa el resumen y pulsa el botón de confirmar el pedido. Ese botón implica una <strong>obligación de pago</strong>.</li>
<li>Te enviaremos por correo electrónico la confirmación con el número de pedido y, si recoges en tienda, tu código de recogida. También puedes consultar el pedido desde el enlace del correo o desde «Mi cuenta».</li>
</ol>
<p>El contrato se celebra en español (también puedes navegar en catalán e inglés). Guardamos una copia electrónica del pedido, a la que puedes acceder desde el correo de confirmación o desde «Mi cuenta». Si detectas un error en tus datos antes de confirmar, puedes corregirlo en el propio formulario; después, escríbenos cuanto antes.</p>
<h2>5. Formas de entrega</h2>
<ul>
<li><strong>Recogida en tienda (Click &amp; Collect), gratuita:</strong> preparamos tu pedido y te avisamos por correo electrónico cuando esté listo. Puedes recogerlo en [direccion] (horario: [horario]) mostrando tu código de recogida (o el código QR del correo) y un documento de identidad. Otra persona puede recogerlo en tu nombre con el código del pedido. Guardamos el pedido <strong>15 días naturales</strong> desde el aviso; pasado ese plazo sin recogerlo, podremos cancelarlo y, si ya estaba pagado, te devolveremos el importe.</li>
<li><strong>Envío a domicilio:</strong> cuando esté disponible, el precio, el importe mínimo para envío gratuito y los plazos se mostrarán en la cesta antes de confirmar el pedido.</li>
</ul>
<h2>6. Formas de pago</h2>
<ul>
<li><strong>Pago en tienda al recoger el pedido</strong>, por los medios aceptados en la tienda.</li>
<li><strong>Pago en línea</strong> (tarjeta, Bizum o PayPal), cuando esté disponible. Los pagos se harán en las plataformas seguras de las entidades de pago; nosotros no vemos ni guardamos los datos de tu tarjeta.</li>
</ul>
<p>Te entregaremos el justificante de compra. Si necesitas factura completa a nombre de una empresa o profesional, indícanos tus datos fiscales (NIF y dirección) al hacer el pedido o al recogerlo.</p>
<h2>7. Derecho de desistimiento</h2>
<p>Si eres consumidor, tienes derecho a desistir de tu compra en un plazo de <strong>14 días naturales</strong> sin necesidad de justificarlo, de acuerdo con los artículos 102 y siguientes del Real Decreto Legislativo 1/2007 (Ley General para la Defensa de los Consumidores y Usuarios). El plazo empieza el día en que tú, o la persona que indiques, recoges o recibes el producto. Cómo hacerlo, plazos de reembolso, excepciones y el formulario están en <a href="/pagina/envios-y-devoluciones">Envíos, recogida y devoluciones</a>.</p>
<h2>8. Garantía</h2>
<p>Todos los productos nuevos tienen la <strong>garantía legal de conformidad de 3 años</strong> desde la entrega (artículos 114 y siguientes de la Ley General para la Defensa de los Consumidores y Usuarios). Si un producto no es conforme, puedes elegir entre su reparación o su sustitución, sin coste para ti; si no fuera posible o no se hiciera en un plazo razonable, podrás pedir una rebaja del precio o la devolución del importe. Para usar la garantía, tráelo a la tienda o escríbenos a [email] con el número de pedido. Algunos fabricantes ofrecen además una garantía comercial, que no sustituye a la legal.</p>
<h2>9. Atención al cliente y reclamaciones</h2>
<p>Puedes contactarnos en [email], en el [telefono] (también por WhatsApp) o en la tienda. Responderemos a las reclamaciones en el menor plazo posible y, como máximo, en un mes. Tienes a tu disposición hojas oficiales de queja, reclamación y denuncia en la tienda y en el portal de la Agència Catalana del Consum (consum.gencat.cat). También puedes acudir a la Junta Arbitral de Consum de Catalunya o a los servicios de consumo de tu municipio.</p>
<h2>10. Ley aplicable y jurisdicción</h2>
<p>Estas condiciones se rigen por la legislación española. Si eres consumidor, para cualquier controversia serán competentes los juzgados y tribunales de tu domicilio.</p>
HTML;
    }

    public static function shippingAndReturns(): string
    {
        return <<<'HTML'
<h2>1. Recogida en tienda (Click &amp; Collect)</h2>
<ul>
<li><strong>Gratuita.</strong> Haz tu pedido en línea y recógelo en [direccion].</li>
<li>Te avisamos por correo electrónico en cuanto está preparado. Horario de recogida: [horario].</li>
<li>Muestra el código de recogida (o el código QR del correo) y un documento de identidad. Otra persona puede recogerlo por ti con el código del pedido.</li>
<li>Guardamos tu pedido <strong>15 días naturales</strong> desde el aviso de que está listo.</li>
</ul>
<h2>2. Envío a domicilio</h2>
<p>Estamos preparando el envío a domicilio para la Península y Baleares. Cuando esté disponible, verás en la cesta el precio, el importe a partir del cual es gratuito y el plazo de entrega antes de confirmar el pedido. Por ahora no enviamos a Canarias, Ceuta ni Melilla.</p>
<h2>3. Devoluciones: derecho de desistimiento (14 días)</h2>
<p>Si eres consumidor, puedes devolver tu compra en línea en un plazo de <strong>14 días naturales</strong> desde que recoges o recibes el producto, sin indicar el motivo.</p>
<h3>Cómo devolver un producto</h3>
<ol>
<li><strong>Comunícanos tu decisión</strong> dentro del plazo: escríbenos a [email], ven a la tienda o envíanos el formulario de abajo (no es obligatorio usarlo). Indica tu número de pedido.</li>
<li><strong>Devuélvenos el producto</strong> en un plazo máximo de 14 días naturales desde que nos comunicas tu decisión. La forma más fácil es traerlo a la tienda, sin coste. Si prefieres enviarlo, el coste del envío de vuelta corre de tu cuenta.</li>
<li>Devuélvelo en buen estado, con sus accesorios y, a ser posible, en su embalaje. Solo responderás de la pérdida de valor que se deba a una manipulación distinta de la necesaria para comprobar su naturaleza, características y funcionamiento.</li>
</ol>
<h3>Reembolso</h3>
<p>Te devolveremos todo lo que hayas pagado por el producto, incluidos los gastos de envío iniciales estándar si los hubo, en un plazo máximo de <strong>14 días naturales</strong> desde que nos comuniques el desistimiento, con el mismo medio de pago que usaste (salvo que acordemos otro, sin coste para ti). Podemos esperar a tener el producto, o a que nos demuestres que lo has enviado, antes de reembolsar. Si pagaste en la tienda en efectivo, te reembolsaremos en efectivo o por transferencia.</p>
<h3>Productos que no se pueden devolver por desistimiento</h3>
<ul>
<li>Productos fabricados o personalizados según tus indicaciones.</li>
<li>Productos precintados que, por razones de higiene o de protección de la salud, se hayan desprecintado tras la entrega (por ejemplo, cosméticos o artículos de higiene personal).</li>
<li>Productos que puedan deteriorarse o caducar con rapidez.</li>
<li>Grabaciones de sonido o vídeo y programas informáticos precintados que hayas desprecintado.</li>
</ul>
<h2>4. Productos defectuosos o equivocados</h2>
<p>Si un producto llega defectuoso, no funciona o no es el que pediste, avísanos cuanto antes en [email], por WhatsApp al [telefono] o en la tienda. Lo repararemos o cambiaremos sin coste, o te devolveremos el dinero, según la <strong>garantía legal de 3 años</strong> (ver <a href="/pagina/condiciones-generales">Condiciones generales</a>). En estos casos no pagas ningún gasto de devolución.</p>
<h2>5. Compras hechas en la tienda física</h2>
<p>El derecho de desistimiento de 14 días se aplica a las compras hechas en la web. Las compras hechas directamente en la tienda física se rigen por la política de cambios de la tienda, que te informaremos en caja, y siempre por la garantía legal.</p>
<h2>6. Modelo de formulario de desistimiento</h2>
<p><em>(Solo debe cumplimentar y enviar el presente formulario si desea desistir del contrato).</em></p>
<ul>
<li>A la atención de [nombre_legal] ([nombre_tienda]), [direccion], correo electrónico [email]:</li>
<li>Por la presente le comunico/comunicamos (*) que desisto de/desistimos de (*) mi/nuestro (*) contrato de venta del siguiente bien/prestación del siguiente servicio (*):</li>
<li>Pedido el/recibido el (*):</li>
<li>Número de pedido:</li>
<li>Nombre del consumidor o de los consumidores:</li>
<li>Domicilio del consumidor o de los consumidores:</li>
<li>Firma del consumidor o de los consumidores (solo si el presente formulario se presenta en papel):</li>
<li>Fecha:</li>
</ul>
<p>(*) Táchese lo que no proceda.</p>
HTML;
    }
}
