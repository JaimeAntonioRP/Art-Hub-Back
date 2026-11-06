# ArtHub · Opciones de pasarela de pago (Perú)

> Igual en **Art-Hub-Back** y **Art-Hub-Front**. Datos consultados el **6 de noviembre de 2026**, en las páginas oficiales de cada proveedor (fuentes al final de cada sección).
> «No encontrado» = no había un dato verificable; no se estimó. Las tasas finales se negocian según el rubro y el volumen.
> **El pago en línea está fuera del contrato actual (§29).** Este documento sirve para decidir; no hay nada integrado todavía.

## Resumen

| | **Culqi** | **Izipay** | **Niubiz** | **Mercado Pago** | **PayPal** |
|---|---|---|---|---|---|
| Comisión publicada | Tarjeta nacional 3.44% + US$0.20. Internacional 3.99% (+US$0.20). La página dice «inafectas a IGV». | Link de pago: 3.44% + IGV | Sin tarifa fija; se negocia. Referencia BCRP: crédito prom. 2.98% | 3.49% + S/1.00 + IGV (al instante) o 3.29% + S/1.00 + IGV (a 14 días) | 5.40% + US$0.30 (hasta 4.40% por volumen), más 3.5% por conversión |
| Afiliación / mensualidad | S/0 / sin mensualidad | Desde S/0 / no encontrado | No encontrado | Cuenta gratis, sin costos fijos | No encontrado |
| Yape · Plin · PagoEfectivo · cuotas | Sí (Yape ≤ S/2,000) · vía PagoEfectivo · Sí · Cuotéalo BCP | Sí · Sí · no encontrado · coordinadas con bancos | Sí · Sí · Sí · según la tarjeta | Sí · no encontrado · Sí · Sí | No · No · No · no encontrado |
| Abono al comercio | 2 días hábiles | Siguiente día útil | 48 h útiles tras la liquidación | Al instante o a 14 días | Retiro: PEN 4.00, o 1% en USD (mín. US$10) |
| Monedas | PEN y USD | No encontrado | PEN y DCC (cobro en la moneda del cliente extranjero) | PEN (USD no confirmado) | USD y otras; PEN no figura |
| Integración con Laravel | SDK PHP oficial, checkout JS, API REST, webhooks, pruebas | SDK JS, API, IPN, sandbox; **ejemplos oficiales en Laravel** | checkout.js + API REST, sandbox y certificación obligatoria; sin SDK PHP | Checkout Pro (redirect), SDK PHP oficial, webhooks, pruebas | SDK PHP oficial y paquete Laravel comunitario `srmklive/paypal` |
| Antifraude | 3DS obligatorio + antifraude | «Protección contra fraudes» (3DS no encontrado) | Cybersource Decision Manager | 3DS 2.0 + antifraude | No encontrado |
| Costo de un contracargo | No encontrado | US$2.00 + impuestos (Visa) | No encontrado | No encontrado | US$10.00 |

**Comisión en una obra de S/ 10,000** (según tarifas publicadas):
- **Culqi:** unos S/ 344 + US$0.20.
- **Izipay:** unos S/ 406.
- **Mercado Pago** (al instante): unos S/ 413.
- **PayPal:** unos S/ 540, más la conversión.

**Descartados:**
- **Stripe** no opera para comercios peruanos (Perú no figura en stripe.com/global).
- **Paddle** y **Lemon Squeezy** prohíben los productos físicos; Lemon Squeezy prohíbe también los marketplaces.
- **dLocal** cubre Perú, pero está orientado a comercios globales y no publica precios.

## Recomendación

1. **Para empezar: Culqi (CulqiOnline).**
   - Afiliación y mensualidad en S/0, y la comisión publicada más baja.
   - Cobra en PEN y USD.
   - El **3DS es obligatorio**, lo que protege mejor las ventas caras frente a contracargos.
   - Incluye Yape, PagoEfectivo y cuotas, y tiene SDK PHP oficial.
2. **Alternativa: Izipay.**
   - Abona al día siguiente y tiene ejemplos oficiales en Laravel.
   - Su «dispersión de fondos» podría servir más adelante para pagar a los artistas (condiciones no revisadas).
3. **Complementos:**
   - **PayPal**, solo para compradores extranjeros que lo prefieran; es caro.
   - **Niubiz**, cuando el volumen permita negociar tasa (tiene antifraude Cybersource y DCC).

**Advertencias para obras caras:**
- **Yape no sirve** para la mayoría de las obras: tiene un tope de S/ 2,000 por pago.
- Ningún proveedor publica un límite de tarjeta por transacción. **Confirmar con el ejecutivo comercial** que una venta de S/ 10,000 a S/ 50,000 no quede bloqueada por el antifraude.

**Cómo encajaría en ArtHub** (cuando se decida y se acuerde el alcance):
- El cliente paga desde la ficha o desde «Mi cuenta» con el checkout de la pasarela.
- El webhook de la pasarela registra la **venta** en el panel y dispara el aviso a info@arthub.com.pe. Las dos cosas ya existen y hoy se hacen a mano.
- Se puede empezar por un **link de pago**: el asesor lo envía tras la solicitud. Casi no requiere programación.

## Fuentes (consultadas el 06/11/2026)

- **Culqi:**
  - Precios: https://culqi.com/precios/
  - Tarifario BCRP: https://www.culqi.com/static/files/tarifario_jul_26.pdf
  - Manual (abono en 2 días hábiles): https://culqi.com/static/files/manuals/manual-culqipanel.pdf
  - Documentación: https://docs.culqi.com/es/documentacion/
  - 3DS: https://docs.culqi.com/es/documentacion/culqi-3ds/
  - Yape: https://docs.culqi.com/es/documentacion/pagos-online/cargo-unico/tokens-yape
  - SDK PHP: https://github.com/culqi/culqi-php
- **Izipay:**
  - Izipay online: https://www.izipay.pe/izipay-online
  - Link de pago: https://www.izipay.pe/link-de-pago
  - Tarifario: https://www.izipay.pe/pdf/tarifario
  - Desarrolladores: https://developers.izipay.pe/
  - Ejemplos oficiales: https://github.com/izipay-pe
- **Niubiz:**
  - Tarifario: https://www.niubiz.com.pe/legales/tarifario-niubiz
  - Pago Web: https://www.niubiz.com.pe/soluciones/pago-web
  - Desarrolladores: https://desarrolladores.niubiz.com.pe/
- **Mercado Pago:**
  - Checkout: https://www.mercadopago.com.pe/herramientas-para-vender/check-out
  - Link de pago: https://www.mercadopago.com.pe/herramientas-para-vender/link-de-pago
  - Checkout Pro: https://www.mercadopago.com.pe/developers/es/docs/checkout-pro/overview
- **PayPal:**
  - Tarifas: https://www.paypal.com/pe/webapps/mpp/merchant-fees
  - SDK PHP: https://packagist.org/packages/paypal/paypal-server-sdk
- **Otros:**
  - Stripe: https://stripe.com/global
  - dLocal: https://docs.dlocal.com/docs/peru
  - Paddle: https://www.paddle.com/help/start/intro-to-paddle/what-am-i-not-allowed-to-sell-on-paddle
  - Lemon Squeezy: https://docs.lemonsqueezy.com/help/getting-started/prohibited-products
