<?php

namespace App\Domain\Content\Blocks;

/**
 * Default storefront content: the Google Stitch "Tot & Més Barcelona" texts in Spanish, Catalan and English.
 * Used to seed the home page and the header/footer settings, and as the fallback before staff edit them.
 *
 * GENERATED from the storefront translations; edit the content in the admin, not here.
 */
final class DefaultContent
{
    /** @return list<array{type: string, data: array<string, mixed>}> */
    public static function home(): array
    {
        return [
            [
                'type' => 'service_strip',
                'data' => [
                    'label' => [
                        'es' => 'Servicio Barcelona Express',
                        'ca' => 'Servei Barcelona Express',
                        'en' => 'Barcelona Express Service',
                    ],
                    'text' => [
                        'es' => 'Recogida gratuita en tienda en 2 horas o envío local el mismo día en Barcelona ciudad.',
                        'ca' => 'Recollida gratuïta a la botiga en 2 hores o enviament local el mateix dia a Barcelona ciutat.',
                        'en' => 'Free in-store pickup in 2 hours or same-day local delivery in Barcelona city.',
                    ],
                    'featured_label' => [
                        'es' => 'Destacados:',
                        'ca' => 'Destacats:',
                        'en' => 'Featured:',
                    ],
                    'show_categories' => true,
                ],
            ],
            [
                'type' => 'hero',
                'data' => [
                    'eyebrow' => [
                        'es' => 'Tu Gran Bazar en Barcelona • totymes.com',
                        'ca' => 'El teu Gran Basar a Barcelona • totymes.com',
                        'en' => 'Your Big Bazaar in Barcelona • totymes.com',
                    ],
                    'title' => [
                        'es' => 'Todo para tu hogar y tu día a día en Barcelona',
                        'ca' => 'Tot per a la teva llar i el teu dia a dia a Barcelona',
                        'en' => 'Everything for your home and everyday life in Barcelona',
                    ],
                    'text' => [
                        'es' => 'Tu gran bazar de confianza ahora también online. Miles de artículos en decoración, cocina, papelería, bricolaje y electrónica con recogida rápida en tienda o entrega en 24/48h.',
                        'ca' => 'El teu gran basar de confiança ara també en línia. Milers d\'articles de decoració, cuina, papereria, bricolatge i electrònica amb recollida ràpida a la botiga o lliurament en 24/48 h.',
                        'en' => 'Your trusted big bazaar, now online too. Thousands of items in home decor, kitchen, stationery, DIY and electronics, with fast in-store pickup or 24/48h delivery.',
                    ],
                    'primary_label' => [
                        'es' => 'Explorar Catálogo Online',
                        'ca' => 'Explora el Catàleg en Línia',
                        'en' => 'Browse Online Catalogue',
                    ],
                    'primary_url' => '#novedades',
                    'secondary_label' => [
                        'es' => 'Cómo llegar a nuestra tienda física',
                        'ca' => 'Com arribar a la nostra botiga física',
                        'en' => 'How to get to our shop',
                    ],
                    'secondary_url' => '[mapa]',
                    'stats' => [
                        [
                            'value' => [
                                'es' => 'Stock Real',
                                'ca' => 'Estoc Real',
                                'en' => 'Real Stock',
                            ],
                            'label' => [
                                'es' => 'En tienda física BCN',
                                'ca' => 'A la botiga física de BCN',
                                'en' => 'In our BCN shop',
                            ],
                        ],
                        [
                            'value' => [
                                'es' => '+15.000',
                                'ca' => '+15.000',
                                'en' => '+15.000',
                            ],
                            'label' => [
                                'es' => 'Referencias activas',
                                'ca' => 'Referències actives',
                                'en' => 'Products in stock',
                            ],
                        ],
                        [
                            'value' => [
                                'es' => '2 Horas',
                                'ca' => '2 Hores',
                                'en' => '2 Hours',
                            ],
                            'label' => [
                                'es' => 'Click & Collect gratis',
                                'ca' => 'Click & Collect gratuït',
                                'en' => 'Free Click & Collect',
                            ],
                        ],
                    ],
                    'mosaic' => 'newest',
                    'mosaic_badge' => [
                        'es' => 'Gran de Gràcia, BCN',
                        'ca' => 'Gran de Gràcia, BCN',
                        'en' => 'Gran de Gràcia, BCN',
                    ],
                ],
            ],
            [
                'type' => 'campaign',
                'data' => [],
            ],
            [
                'type' => 'categories',
                'data' => [
                    'eyebrow' => [
                        'es' => 'Departamentos [nombre_tienda]',
                        'ca' => 'Departaments [nombre_tienda]',
                        'en' => '[nombre_tienda] departments',
                    ],
                    'title' => [
                        'es' => 'Explora por Categorías de Tienda',
                        'ca' => 'Explora per Categories de Botiga',
                        'en' => 'Shop by Department',
                    ],
                    'link_label' => [
                        'es' => 'Ver todos los departamentos',
                        'ca' => 'Veure tots els departaments',
                        'en' => 'See all departments',
                    ],
                    'limit' => 6,
                ],
            ],
            [
                'type' => 'products',
                'data' => [
                    'anchor' => 'novedades',
                    'title' => [
                        'es' => 'Novedades en [nombre_tienda] Barcelona',
                        'ca' => 'Novetats a [nombre_tienda] Barcelona',
                        'en' => 'New at [nombre_tienda] Barcelona',
                    ],
                    'badge' => [
                        'es' => 'En stock en tienda BCN',
                        'ca' => 'En estoc a la botiga de BCN',
                        'en' => 'In stock at BCN shop',
                    ],
                    'text' => [
                        'es' => 'Artículos nuevos directos de almacén y tienda física. Disponibles para Click & Collect en 2h o envío rápido a domicilio.',
                        'ca' => 'Articles nous directes del magatzem i de la botiga física. Disponibles per a Click & Collect en 2 h o enviament ràpid a domicili.',
                        'en' => 'New arrivals straight from our warehouse and shop. Available for Click & Collect in 2h or fast home delivery.',
                    ],
                    'all_label' => [
                        'es' => 'Todo el catálogo',
                        'ca' => 'Tot el catàleg',
                        'en' => 'Whole catalogue',
                    ],
                    'load_more_label' => [
                        'es' => 'Cargar más artículos de la tienda',
                        'ca' => 'Carrega més articles de la botiga',
                        'en' => 'Load more items',
                    ],
                    'count_text' => [
                        'es' => 'Mostrando [mostrados] de [total] artículos en stock en Barcelona',
                        'ca' => 'Mostrant [mostrados] de [total] articles en estoc a Barcelona',
                        'en' => 'Showing [mostrados] of [total] items in stock in Barcelona',
                    ],
                    'source' => 'newest',
                    'limit' => 12,
                    'initial' => 8,
                    'show_filters' => true,
                ],
            ],
            [
                'type' => 'features',
                'data' => [
                    'badge' => [
                        'es' => 'Garantía Oficial [nombre_tienda]',
                        'ca' => 'Garantia Oficial [nombre_tienda]',
                        'en' => 'Official [nombre_tienda] Guarantee',
                    ],
                    'title' => [
                        'es' => 'Comprar en nuestro bazar es rápido, cercano y 100% seguro',
                        'ca' => 'Comprar al nostre basar és ràpid, proper i 100% segur',
                        'en' => 'Shopping at our bazaar is quick, friendly and 100% secure',
                    ],
                    'text' => [
                        'es' => 'Como comercio local con tienda física en Barcelona, respondemos de cada producto con ticket oficial, 3 años de garantía y devolución fácil en mostrador o por correo.',
                        'ca' => 'Com a comerç local amb botiga física a Barcelona, responem de cada producte amb tiquet oficial, 3 anys de garantia i devolució fàcil al taulell o per correu.',
                        'en' => 'As a local business with a shop in Barcelona, we stand behind every product with an official receipt, a 3-year guarantee and easy returns in store or by post.',
                    ],
                    'link_label' => [
                        'es' => 'Conoce nuestras condiciones de garantía',
                        'ca' => 'Coneix les nostres condicions de garantia',
                        'en' => 'Read our guarantee terms',
                    ],
                    'link_url' => '/pagina/envios-y-devoluciones',
                    'cards' => [
                        [
                            'icon' => 'building-storefront',
                            'title' => [
                                'es' => 'Click & Collect 2h',
                                'ca' => 'Click & Collect 2 h',
                                'en' => 'Click & Collect 2h',
                            ],
                            'text' => [
                                'es' => 'Pide online y recoge en nuestro mostrador de Barcelona en 2 horas sin gastos de envío.',
                                'ca' => 'Demana en línia i recull a la nostra botiga de Barcelona en 2 hores sense despeses d\'enviament.',
                                'en' => 'Order online and pick up at our Barcelona counter in 2 hours with no shipping costs.',
                            ],
                        ],
                        [
                            'icon' => 'truck',
                            'title' => [
                                'es' => 'Envío 24/48h',
                                'ca' => 'Enviament 24/48 h',
                                'en' => '24/48h delivery',
                            ],
                            'text' => [
                                'es' => 'Gratis en Barcelona > 25€ y envíos a toda la Península con SEUR y Correos Express.',
                                'ca' => 'Gratuït a Barcelona > 25 € i enviaments a tota la Península amb SEUR i Correos Express.',
                                'en' => 'Free in Barcelona over €25 and delivery across mainland Spain with SEUR and Correos Express.',
                            ],
                        ],
                        [
                            'icon' => 'credit-card',
                            'title' => [
                                'es' => 'Bizum, Tarjeta o Tienda',
                                'ca' => 'Bizum, Targeta o Botiga',
                                'en' => 'Bizum, Card or In Store',
                            ],
                            'text' => [
                                'es' => 'Paga seguro en la web o abona tu pedido al recogerlo directamente en nuestra tienda física.',
                                'ca' => 'Paga de manera segura al web o abona la comanda quan la recullis a la nostra botiga física.',
                                'en' => 'Pay securely online or pay for your order when you collect it in our shop.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'info_cards',
                'data' => [
                    'eyebrow' => [
                        'es' => 'Comercio de Proximidad',
                        'ca' => 'Comerç de Proximitat',
                        'en' => 'Local Shop',
                    ],
                    'title' => [
                        'es' => 'Visítanos en nuestra Tienda Física de Barcelona',
                        'ca' => 'Visita\'ns a la nostra Botiga Física de Barcelona',
                        'en' => 'Visit our Shop in Barcelona',
                    ],
                    'link_label' => [
                        'es' => 'Ver mapa y horarios',
                        'ca' => 'Veure mapa i horaris',
                        'en' => 'See map and opening hours',
                    ],
                    'link_url' => '[mapa]',
                    'cards' => [
                        [
                            'icon' => 'map-pin',
                            'title' => [
                                'es' => 'Ubicación & Horario',
                                'ca' => 'Ubicació & Horari',
                                'en' => 'Location & Opening Hours',
                            ],
                            'text' => [
                                'es' => 'Carrer Gran de Gràcia (cerca de Metro Diagonal y Fontana), Barcelona. Amplia tienda con más de 400 m² de exposición.',
                                'ca' => 'Carrer Gran de Gràcia (a prop del Metro Diagonal i Fontana), Barcelona. Àmplia botiga amb més de 400 m² d\'exposició.',
                                'en' => 'Carrer Gran de Gràcia (near Diagonal and Fontana metro), Barcelona. A large shop with over 400 m² of display space.',
                            ],
                            'highlight_title' => [
                                'es' => 'Lunes a Sábado: 09:30 - 20:30 h',
                                'ca' => 'Dilluns a dissabte: 09:30 - 20:30 h',
                                'en' => 'Monday to Saturday: 09:30 - 20:30',
                            ],
                            'highlight_text' => [
                                'es' => 'Abierto ininterrumpidamente',
                                'ca' => 'Obert ininterrompudament',
                                'en' => 'Open all day',
                            ],
                            'footer_icon' => 'map-pin',
                            'footer' => [
                                'es' => 'Barcelona ciudad',
                                'ca' => 'Barcelona ciutat',
                                'en' => 'Barcelona city',
                            ],
                        ],
                        [
                            'icon' => 'bolt',
                            'title' => [
                                'es' => 'Click & Collect Express',
                                'ca' => 'Click & Collect Express',
                                'en' => 'Click & Collect Express',
                            ],
                            'text' => [
                                'es' => 'Compra cómodamente en totymes.com y ten tu paquete preparado y empaquetado en solo 2 horas. Recoge sin hacer cola en mostrador prioritario.',
                                'ca' => 'Compra còmodament a totymes.com i tingues el paquet preparat i empaquetat en només 2 hores. Recull-lo sense fer cua al taulell prioritari.',
                                'en' => 'Shop easily at totymes.com and have your order packed and ready in just 2 hours. Skip the queue at our priority counter.',
                            ],
                            'highlight_title' => [
                                'es' => '100% Gratuito',
                                'ca' => '100% Gratuït',
                                'en' => '100% Free',
                            ],
                            'highlight_text' => [
                                'es' => 'Sin pedido mínimo',
                                'ca' => 'Sense comanda mínima',
                                'en' => 'No minimum order',
                            ],
                            'footer_icon' => 'check-badge',
                            'footer' => [
                                'es' => 'Listo hoy mismo',
                                'ca' => 'A punt avui mateix',
                                'en' => 'Ready today',
                            ],
                        ],
                        [
                            'icon' => 'truck',
                            'title' => [
                                'es' => 'Envíos a Domicilio',
                                'ca' => 'Enviaments a Domicili',
                                'en' => 'Home Delivery',
                            ],
                            'text' => [
                                'es' => 'Reparto diario en Barcelona con mensajería local y envíos peninsulares con Correos Express y SEUR. Seguimiento de paquete en tiempo real.',
                                'ca' => 'Repartiment diari a Barcelona amb missatgeria local i enviaments peninsulars amb Correos Express i SEUR. Seguiment del paquet en temps real.',
                                'en' => 'Daily delivery in Barcelona by local courier and across mainland Spain with Correos Express and SEUR. Real-time parcel tracking.',
                            ],
                            'highlight_title' => [
                                'es' => 'Gratis en BCN > 25€',
                                'ca' => 'Gratuït a BCN > 25 €',
                                'en' => 'Free in BCN over €25',
                            ],
                            'highlight_text' => [
                                'es' => 'Resto de Península desde 3,95€',
                                'ca' => 'Resta de la Península des de 3,95 €',
                                'en' => 'Rest of mainland Spain from €3.95',
                            ],
                            'footer_icon' => 'clock',
                            'footer' => [
                                'es' => 'Entrega en 24/48h',
                                'ca' => 'Lliurament en 24/48 h',
                                'en' => 'Delivery in 24/48h',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'data' => [
                    'icon' => 'archive-box',
                    'style' => 'navy',
                    'title' => [
                        'es' => '¿Buscas algún artículo específico para tu casa o negocio?',
                        'ca' => 'Busques algun article específic per a casa teva o el teu negoci?',
                        'en' => 'Looking for something specific for your home or business?',
                    ],
                    'text' => [
                        'es' => 'En nuestra tienda física disponemos de más de 15.000 referencias. Consúltanos por WhatsApp o haz tu pedido directo online con entrega en mano o a domicilio.',
                        'ca' => 'A la nostra botiga física tenim més de 15.000 referències. Consulta\'ns per WhatsApp o fes la comanda directament en línia amb lliurament en mà o a domicili.',
                        'en' => 'Our shop stocks more than 15,000 products. Ask us on WhatsApp or order online for pickup or home delivery.',
                    ],
                    'primary_label' => [
                        'es' => 'Comprar en el catálogo online',
                        'ca' => 'Compra al catàleg en línia',
                        'en' => 'Shop the online catalogue',
                    ],
                    'primary_url' => '#novedades',
                    'secondary_label' => [
                        'es' => 'Contactar con la tienda',
                        'ca' => 'Contacta amb la botiga',
                        'en' => 'Contact the shop',
                    ],
                    'secondary_url' => '[whatsapp]',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function layout(): array
    {
        return [
            'announcement' => [
                'text' => [
                    'es' => 'Visita nuestra tienda física en Gràcia (Barcelona) o compra online',
                    'ca' => 'Visita la nostra botiga física a Gràcia (Barcelona) o compra en línia',
                    'en' => 'Visit our shop in Gràcia (Barcelona) or shop online',
                ],
                'extra' => [
                    'es' => 'Envío gratis en Barcelona > 25€ • Código:',
                    'ca' => 'Enviament gratuït a Barcelona > 25 € • Codi:',
                    'en' => 'Free delivery in Barcelona over €25 • Code:',
                ],
                'code' => 'TOTSPRING',
                'pill' => [
                    'es' => 'Tienda abierta hoy hasta 20:30 h',
                    'ca' => 'Botiga oberta avui fins a les 20:30 h',
                    'en' => 'Shop open today until 20:30',
                ],
            ],
            'header' => [
                'store_pill' => [
                    'es' => 'Tienda Barcelona (Gran de Gràcia)',
                    'ca' => 'Botiga Barcelona (Gran de Gràcia)',
                    'en' => 'Barcelona shop (Gran de Gràcia)',
                ],
                'store_pill_url' => '#contacto',
                'search_placeholder' => [
                    'es' => 'Buscar en más de 15.000 artículos de cocina, hogar, papelería, bricolaje...',
                    'ca' => 'Cerca entre més de 15.000 articles de cuina, llar, papereria, bricolatge...',
                    'en' => 'Search over 15,000 kitchen, home, stationery and DIY items...',
                ],
                'tagline' => [
                    'es' => 'Tienda · Online',
                    'ca' => 'Botiga · Online',
                    'en' => 'Shop · Online',
                ],
            ],
            'value_props' => [
                [
                    'icon' => 'shield-check',
                    'title' => [
                        'es' => 'Garantía [nombre_tienda] Oficial',
                        'ca' => 'Garantia [nombre_tienda] Oficial',
                        'en' => 'Official [nombre_tienda] Guarantee',
                    ],
                    'text' => [
                        'es' => 'Ticket oficial y atención directa en mostrador de Barcelona.',
                        'ca' => 'Tiquet oficial i atenció directa al taulell de Barcelona.',
                        'en' => 'Official receipt and in-person help at our Barcelona counter.',
                    ],
                ],
                [
                    'icon' => 'truck',
                    'title' => [
                        'es' => 'Envíos con SEUR & Correos Express',
                        'ca' => 'Enviaments amb SEUR & Correos Express',
                        'en' => 'Shipping with SEUR & Correos Express',
                    ],
                    'text' => [
                        'es' => 'Entrega a domicilio en BCN y resto de la Península.',
                        'ca' => 'Lliurament a domicili a BCN i a la resta de la Península.',
                        'en' => 'Home delivery in BCN and the rest of mainland Spain.',
                    ],
                ],
                [
                    'icon' => 'lock-closed',
                    'title' => [
                        'es' => 'Pagos 100% Seguros (Bizum, Tarjeta)',
                        'ca' => 'Pagaments 100% Segurs (Bizum, Targeta)',
                        'en' => '100% Secure Payments (Bizum, Card)',
                    ],
                    'text' => [
                        'es' => 'Cifrado de última generación y opción de abono en tienda.',
                        'ca' => 'Xifratge d\'última generació i opció de pagament a la botiga.',
                        'en' => 'State-of-the-art encryption, or pay in store.',
                    ],
                ],
            ],
            'footer' => [
                'about' => [
                    'es' => 'Gran bazar y multitienda física en Barcelona. Más de 15.000 referencias para tu hogar, cocina, papelería, bricolaje y ocio. Ahora con venta online en totymes.com con Click & Collect en tienda o envío rápido.',
                    'ca' => 'Gran basar i multibotiga física a Barcelona. Més de 15.000 referències per a la teva llar, cuina, papereria, bricolatge i lleure. Ara amb venda en línia a totymes.com amb Click & Collect a la botiga o enviament ràpid.',
                    'en' => 'Big bazaar and multi-store in Barcelona. Over 15,000 products for your home, kitchen, stationery, DIY and leisure. Now selling online at totymes.com with in-store Click & Collect or fast delivery.',
                ],
                'chip_location' => [
                    'es' => 'Tienda en Barcelona (Gran de Gràcia)',
                    'ca' => 'Botiga a Barcelona (Gran de Gràcia)',
                    'en' => 'Shop in Barcelona (Gran de Gràcia)',
                ],
                'chip_web' => 'totymes.com',
                'columns' => [
                    [
                        'title' => [
                            'es' => 'Comprar Online',
                            'ca' => 'Compra en Línia',
                            'en' => 'Shop Online',
                        ],
                        'links' => [
                            [
                                'label' => [
                                    'es' => 'Catálogo Completo',
                                    'ca' => 'Catàleg Complet',
                                    'en' => 'Full Catalogue',
                                ],
                                'url' => '/#novedades',
                            ],
                            [
                                'label' => [
                                    'es' => 'Ofertas del momento',
                                    'ca' => 'Ofertes del moment',
                                    'en' => 'Current offers',
                                ],
                                'url' => '/#novedades',
                            ],
                            [
                                'label' => [
                                    'es' => 'Tarifas y Envíos',
                                    'ca' => 'Tarifes i Enviaments',
                                    'en' => 'Shipping and Rates',
                                ],
                                'url' => '/pagina/envios-y-devoluciones',
                            ],
                            [
                                'label' => [
                                    'es' => 'Click & Collect 2h',
                                    'ca' => 'Click & Collect 2 h',
                                    'en' => 'Click & Collect 2h',
                                ],
                                'url' => '/pagina/envios-y-devoluciones',
                            ],
                        ],
                    ],
                    [
                        'title' => [
                            'es' => 'Tienda Física & Ayuda',
                            'ca' => 'Botiga Física & Ajuda',
                            'en' => 'Shop & Help',
                        ],
                        'links' => [
                            [
                                'label' => [
                                    'es' => 'Cómo llegar a Gràcia',
                                    'ca' => 'Com arribar a Gràcia',
                                    'en' => 'How to get to Gràcia',
                                ],
                                'url' => '[mapa]',
                            ],
                            [
                                'label' => [
                                    'es' => 'Horarios de apertura',
                                    'ca' => 'Horaris d\'obertura',
                                    'en' => 'Opening hours',
                                ],
                                'url' => '#contacto',
                            ],
                            [
                                'label' => [
                                    'es' => 'Atención por WhatsApp',
                                    'ca' => 'Atenció per WhatsApp',
                                    'en' => 'WhatsApp support',
                                ],
                                'url' => '[whatsapp]',
                            ],
                            [
                                'label' => [
                                    'es' => 'Preguntas frecuentes',
                                    'ca' => 'Preguntes freqüents',
                                    'en' => 'FAQ',
                                ],
                                'url' => '/pagina/preguntas-frecuentes',
                            ],
                        ],
                    ],
                ],
                'copyright' => [
                    'es' => '© [año] [empresa] - Bazar & Multitienda Barcelona (totymes.com). Todos los derechos reservados.',
                    'ca' => '© [año] [empresa] - Basar & Multibotiga Barcelona (totymes.com). Tots els drets reservats.',
                    'en' => '© [año] [empresa] - Bazaar & Multi-store Barcelona (totymes.com). All rights reserved.',
                ],
                'payments_note' => [
                    'es' => 'Pago seguro: Bizum, Tarjeta o en Tienda',
                    'ca' => 'Pagament segur: Bizum, Targeta o a la Botiga',
                    'en' => 'Secure payment: Bizum, Card or In Store',
                ],
                'carriers_note' => [
                    'es' => 'SEUR & Correos Express',
                    'ca' => 'SEUR & Correos Express',
                    'en' => 'SEUR & Correos Express',
                ],
            ],
        ];
    }
}
