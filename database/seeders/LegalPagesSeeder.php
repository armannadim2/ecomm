<?php

namespace Database\Seeders;

use App\Domain\Content\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the legal pages a Spanish online shop needs, as DRAFTS.
 * The texts are starting templates only: the owner must review them
 * (ideally with a legal adviser) before publishing.
 *
 * Safe to run again: existing pages are never overwritten.
 */
class LegalPagesSeeder extends Seeder
{
    private const NOTICE = '<p><strong>Plantilla orientativa. Revísala con tu asesor antes de publicarla.</strong></p>';

    public function run(): void
    {
        $pages = [
            'aviso-legal' => ['Aviso legal', $this->legalNotice()],
            'politica-de-privacidad' => ['Política de privacidad', $this->privacy()],
            'politica-de-cookies' => ['Política de cookies', $this->cookies()],
            'condiciones-generales' => ['Condiciones generales de contratación', $this->terms()],
            'envios-y-devoluciones' => ['Envíos y devoluciones', $this->returns()],
        ];

        $position = 0;

        foreach ($pages as $slug => [$title, $body]) {
            Page::query()->withTrashed()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => self::NOTICE.$body,
                'type' => Page::TYPE_LEGAL,
                'is_published' => false,
                'show_in_footer' => true,
                'position' => $position++,
            ]);
        }
    }

    private function legalNotice(): string
    {
        return <<<'HTML'
<h2>Datos identificativos</h2>
<p>En cumplimiento de la Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE), se informa de que este sitio web es titularidad de <strong>[nombre_legal]</strong>, con NIF [nif] y domicilio en [direccion].</p>
<p>Correo electrónico: [email]. Teléfono: [telefono].</p>
<p>[registro_mercantil]</p>
<h2>Condiciones de uso</h2>
<p>El acceso a este sitio web implica la aceptación de estas condiciones. El usuario se compromete a hacer un uso adecuado de los contenidos.</p>
<h2>Propiedad intelectual</h2>
<p>Los contenidos de este sitio (textos, imágenes, logotipos) pertenecen a [nombre_legal] o a sus licenciantes y no pueden reproducirse sin autorización.</p>
HTML;
    }

    private function privacy(): string
    {
        return <<<'HTML'
<h2>Responsable del tratamiento</h2>
<p>[nombre_legal], NIF [nif], [direccion]. Contacto: [email].</p>
<h2>Finalidades</h2>
<ul><li>Gestionar tu cuenta y tus pedidos (base legal: ejecución del contrato).</li><li>Cumplir obligaciones fiscales y contables (obligación legal).</li><li>Enviarte comunicaciones comerciales solo si lo has aceptado (consentimiento).</li></ul>
<h2>Conservación</h2>
<p>Conservamos los datos mientras mantengas tu cuenta y, después, durante los plazos legales (por ejemplo, 6 años para la documentación contable).</p>
<h2>Destinatarios</h2>
<p>Empresas de transporte, entidades de pago y proveedores tecnológicos que actúan como encargados del tratamiento. No vendemos tus datos.</p>
<h2>Tus derechos</h2>
<p>Puedes acceder, rectificar, suprimir, oponerte, limitar el tratamiento y solicitar la portabilidad de tus datos escribiendo a [email]. Desde «Mi cuenta» puedes descargar tus datos y eliminar tu cuenta. También puedes reclamar ante la Agencia Española de Protección de Datos (www.aepd.es).</p>
HTML;
    }

    private function cookies(): string
    {
        return <<<'HTML'
<h2>¿Qué son las cookies?</h2>
<p>Pequeños archivos que se guardan en tu navegador para que la web funcione o para obtener información sobre su uso.</p>
<h2>Cookies que usamos</h2>
<ul><li><strong>Técnicas (necesarias):</strong> sesión, cesta de la compra y seguridad. No requieren consentimiento.</li><li><strong>Analíticas o publicitarias:</strong> solo si las aceptas en el aviso de cookies.</li></ul>
<h2>Cómo gestionarlas</h2>
<p>Puedes cambiar tu elección en cualquier momento desde el enlace «Configurar cookies» del pie de página o desde tu navegador.</p>
HTML;
    }

    private function terms(): string
    {
        return <<<'HTML'
<h2>Vendedor</h2>
<p>[nombre_legal], NIF [nif], [direccion], [email].</p>
<h2>Precios</h2>
<p>Todos los precios incluyen el IVA. Los gastos de envío se indican antes de finalizar la compra.</p>
<h2>Proceso de compra</h2>
<p>Añade los productos a la cesta, indica tus datos de envío, elige la forma de pago y confirma el pedido. Recibirás un correo de confirmación.</p>
<h2>Formas de pago</h2>
<p>Tarjeta, Bizum y PayPal. Los pagos se procesan en las plataformas seguras de las entidades de pago; no almacenamos los datos de tu tarjeta.</p>
<h2>Derecho de desistimiento</h2>
<p>Dispones de 14 días naturales desde la entrega para desistir de la compra sin indicar el motivo. Consulta la página «Envíos y devoluciones».</p>
<h2>Garantía</h2>
<p>Los productos tienen la garantía legal de conformidad de 3 años prevista en la normativa de consumo.</p>
HTML;
    }

    private function returns(): string
    {
        return <<<'HTML'
<h2>Envíos</h2>
<p>Enviamos a la Península y Baleares. Los plazos y costes se muestran en la cesta antes de pagar.</p>
<h2>Devoluciones (derecho de desistimiento)</h2>
<p>Tienes 14 días naturales desde que recibes el pedido para devolverlo. Solicítalo desde «Mi cuenta» o escribiendo a [email]. Te devolveremos el importe, incluidos los gastos de envío iniciales estándar, en un plazo máximo de 14 días desde que nos comuniques tu decisión, utilizando el mismo medio de pago.</p>
<h2>Formulario de desistimiento</h2>
<p>A la atención de [nombre_legal], [direccion], [email]: Por la presente le comunico que desisto de mi contrato de venta del siguiente bien: (pedido nº, fecha de recepción, nombre, dirección, firma si se envía en papel, fecha).</p>
HTML;
    }
}
