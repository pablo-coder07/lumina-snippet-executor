<?php
/*
 * Código generado por DrawCode AI
 * Shortcode: [claude_generated_1754414142]
 * Fecha: 2025-08-05 17:15:45
 * Timestamp: 1754414145
 * User ID: 0
 */

/**
 * Plugin Name: Manada Wear E-commerce
 * Description: Shortcode para mostrar la tienda de Manada Wear
 * Version: 1.0
 * Author: Manada Wear
 */

// Shortcode para mostrar la tienda
function manada_wear_ecommerce_shortcode() {
    ob_start();
    ?>
    <div class="manada-v1-ecommerce-container">
        <!-- Hero Section -->
        <section class="manada-v1-hero">
            <div class="manada-v1-hero-content">
                <h1 class="manada-v1-hero-title">Manada Wear</h1>
                <p class="manada-v1-hero-subtitle">Bordados personalizados de tu mascota</p>
                <p class="manada-v1-hero-description">Bordamos la carita de tu mascota en camisetas, hoodies, gorras y más.</p>
                <a href="#manada-v1-products" class="manada-v1-button">Ver productos</a>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="manada-v1-how-it-works">
            <h2 class="manada-v1-section-title">TU PRENDA PERSONALIZADA FAVORITA EN 4 PASOS</h2>
            <div class="manada-v1-steps-container">
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">1</div>
                    <div class="manada-v1-step-content">
                        <h3>Escoge tu prenda</h3>
                        <p>Elige tu prenda favorita en nuestra web y carga las fotos de tu peludo.</p>
                    </div>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">2</div>
                    <div class="manada-v1-step-content">
                        <h3>Ilustración única</h3>
                        <p>Crearemos una ilustración única para ti y la enviaremos a tu WhatsApp para que realices revisiones.</p>
                    </div>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">3</div>
                    <div class="manada-v1-step-content">
                        <h3>Bordado artesanal</h3>
                        <p>Bordamos tu prenda, la abuela perruna es la encargada de hacer este proceso.</p>
                    </div>
                </div>
                <div class="manada-v1-step">
                    <div class="manada-v1-step-number">4</div>
                    <div class="manada-v1-step-content">
                        <h3>Envío con amor</h3>
                        <p>¡Tu prenda está lista! La empacaremos con mucho amor y te compartiremos la guía para que puedas hacer seguimiento.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Collection -->
        <section class="manada-v1-featured-collection">
            <h2 class="manada-v1-section-title">NUEVA COLECCIÓN: PET MOM ERA</h2>
            <div class="manada-v1-featured-description">
                <p>Celebra el amor por tu mascota con nuestra exclusiva colección "Pet Mom Era"</p>
            </div>
            <div class="manada-v1-products-grid" id="manada-v1-products">
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-badge">Sale!</div>
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Termo In My Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Termo In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 30.000</p>
                        <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                        <a href="#" class="manada-v1-product-details">Ver detalles</a>
                    </div>
                </div>
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Tote Bag In My Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Tote Bag In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 30.000</p>
                        <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                        <a href="#" class="manada-v1-product-details">Ver detalles</a>
                    </div>
                </div>
                <div class="manada-v1-product-card">
                    <div class="manada-v1-product-image">
                        <img src="https://via.placeholder.com/300x300" alt="Camiseta In My Pet Mom Era">
                    </div>
                    <div class="manada-v1-product-info">
                        <h3 class="manada-v1-product-title">Camiseta In My Pet Mom Era</h3>
                        <p class="manada-v1-product-price">$ 70.000</p>
                        <button class="manada-v1-add-to-cart">Añadir al carrito</button>
                        <a href="#" class="manada-v1-product-details">Ver detalles</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Custom Order Section -->
        <section class="manada-v1-custom-order">
            <h2 class="manada-v1-section-title">CREA TU DISEÑO PERSONALIZADO</h2>
            <div class="manada-v1-custom-order-container">
                <div class="manada-v1-custom-order-image">
                    <img src="https://via.placeholder.com/500x400" alt="Diseño personalizado">
                </div>
                <div class="manada-v1-custom-order-form">
                    <h3>Envíanos la foto de tu mascota</h3>
                    <p>Completa el formulario y nos pondremos en contacto contigo para crear tu diseño único.</p>
                    <form id="manada-v1-custom-form">
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-name">Nombre</label>
                            <input type="text" id="manada-v1-name" name="name" required>
                        </div>
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-email">Email</label>
                            <input type="email" id="manada-v1-email" name="email" required>
                        </div>
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-phone">Teléfono / WhatsApp</label>
                            <input type="tel" id="manada-v1-phone" name="phone" required>
                        </div>
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-product">Producto</label>
                            <select id="manada-v1-product" name="product" required>
                                <option value="">Selecciona un producto</option>
                                <option value="camiseta">Camiseta</option>
                                <option value="hoodie">Hoodie</option>
                                <option value="gorra">Gorra</option>
                                <option value="tote">Tote Bag</option>
                                <option value="termo">Termo</option>
                            </select>
                        </div>
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-message">Mensaje</label>
                            <textarea id="manada-v1-message" name="message" rows="4"></textarea>
                        </div>
                        <div class="manada-v1-form-group">
                            <label for="manada-v1-pet-photo">Foto de tu mascota</label>
                            <input type="file" id="manada-v1-pet-photo" name="pet_photo" accept="image/*">
                        </div>
                        <button type="submit" class="manada-v1-submit-button">Enviar</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="manada-v1-testimonials">
            <h2 class="manada-v1-section-title">LO QUE DICEN NUESTROS CLIENTES</h2>
            <div class="manada-v1-testimonials-slider" id="manada-v1-testimonials-slider">
                <div class="manada-v1-testimonial">
                    <div class="manada-v1-testimonial-content">
                        <p>"¡Me encantó mi camiseta personalizada! El bordado es idéntico a mi perrito y la calidad es excelente."</p>
                    </div>
                    <div class="manada-v1-testimonial-author">
                        <img src="https://via.placeholder.com/50x50" alt="Cliente">
                        <div class="manada-v1-author-info">
                            <h4>Laura Gómez</h4>
                            <div class="manada-v1-stars">★★★★★</div>
                        </div>
                    </div>
                </div>
                <div class="manada-v1-testimonial">
                    <div class="manada-v1-testimonial-content">
                        <p>"El proceso fue muy sencillo y el resultado superó mis expectativas. Ahora llevo a mi gato conmigo a todas partes."</p>
                    </div>
                    <div class="manada-v1-testimonial-author">
                        <img src="https://via.placeholder.com/50x50" alt="Cliente">
                        <div class="manada-v1-author-info">
                            <h4>Carlos Martínez</h4>
                            <div class="manada-v1-stars">★★★★★</div>
                        </div>
                    </div>
                </div>
                <div class="manada-v1-testimonial">
                    <div class="manada-v1-testimonial-content">
                        <p>"Regalé una tote bag personalizada a mi hermana y quedó fascinada. El servicio al cliente es excepcional."</p>
                    </div>
                    <div class="manada-v1-testimonial-author">
                        <img src="https://via.placeholder.com/50x50" alt="Cliente">
                        <div class="manada-v1-author-info">
                            <h4>Ana Rodríguez</h4>
                            <div class="manada-v1-stars">★★★★★</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="manada-v1-slider-controls">
                <button id="manada-v1-prev-btn" class="manada-v1-slider-btn">❮</button>
                <button id="manada-v1-next-btn" class="manada-v1-slider-btn">❯</button>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="manada-v1-faq">
            <h2 class="manada-v1-section-title">PREGUNTAS FRECUENTES</h2>
            <div class="manada-v1-accordion" id="manada-v1-accordion">
                <div class="manada-v1-accordion-item">
                    <div class="manada-v1-accordion-header">
                        <h3>¿Cuánto tiempo tarda en llegar mi pedido?</h3>
                        <span class="manada-v1-accordion-icon">+</span>
                    </div>
                    <div class="manada-v1-accordion-content">
                        <p>El tiempo de entrega es de aproximadamente 7-10 días hábiles, ya que cada pieza es personalizada y bordada a mano.</p>
                    </div>
                </div>
                <div class="manada-v1-accordion-item">
                    <div class="manada-v1-accordion-header">
                        <h3>¿Qué tipo de fotos debo enviar?</h3>
                        <span class="manada-v1-accordion-icon">+</span>
                    </div>
                    <div class="manada-v1-accordion-content">
                        <p>Recomendamos enviar fotos claras y de frente de tu mascota, con buena iluminación y donde se pueda apreciar bien su rostro.</p>
                    </div>
                </div>
                <div class="manada-v1-accordion-item">
                    <div class="manada-v1-accordion-header">
                        <h3>¿Puedo solicitar cambios en el diseño?</h3>
                        <span class="manada-v1-accordion-icon">+</span>
                    </div>
                    <div class="manada-v1-accordion-content">
                        <p>¡Claro! Antes de bordar tu prenda, te enviamos el diseño para tu aprobación y puedes solicitar hasta 2 revisiones sin costo adicional.</p>
                    </div>
                </div>
                <div class="manada-v1-accordion-item">
                    <div class="manada-v1-accordion-header">
                        <h3>¿Hacen envíos internacionales?</h3>
                        <span class="manada-v1-accordion-icon">+</span>
                    </div>
                    <div class="manada-v1-accordion-content">
                        <p>Sí, realizamos envíos internacionales. El costo y tiempo de entrega varía según el país de destino.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter -->
        <section class="manada-v1-newsletter">
            <div class="manada-v1-newsletter-container">
                <h2>ÚNETE A NUESTRA MANADA</h2>
                <p>Suscríbete para recibir novedades, promociones exclusivas y consejos para pet lovers.</p>
                <form id="manada-v1-newsletter-form" class="manada-v1-newsletter-form">
                    <input type="email" id="manada-v1-newsletter-email" placeholder="Tu correo electrónico" required>
                    <button type="submit" class="manada-v1-newsletter-button">Suscribirme</button>
                </form>
            </div>
        </section>

        <!-- Contact Info -->
        <section class="manada-v1-contact">
            <h2 class="manada-v1-section-title">CONTÁCTANOS</h2>
            <div class="manada-v1-contact-container">
                <div class="manada-v1-contact-info">
                    <div class="manada-v1-contact-item">
                        <i class="manada-v1-icon">✉️</i>
                        <p>info@manadawear.com</p>
                    </div>
                    <div class="manada-v1-contact-item">
                        <i class="manada-v1-icon">📱</i>
                        <p>+57 300 123 4567</p>
                    </div>
                    <div class="manada-v1-contact-item">
                        <i class="manada-v1-icon">📍</i>
                        <p>Bogotá, Colombia</p>
                    </div>
                </div>
                <div class="manada-v1-social-media">
                    <a href="#" class="manada-v1-social-icon">📷</a>
                    <a href="#" class="manada-v1-social-icon">👤</a>
                    <a href="#" class="manada-v1-social-icon">🐦</a>
                    <a href="#" class="manada-v1-social-icon">📱</a>
                </div>
            </div>
        </section>
    </div>

    <style>
        /* General Styles */
        .manada-v1-ecommerce-container {
            font-family: 'Poppins', sans-serif;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }

        .manada-v1-ecommerce-container * {
            box-sizing: border-box;
        }

        .manada-v1-section-title {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: #333;
            position: relative;
            padding-bottom: 15px;
        }

        .manada-v1-section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #ff6b6b;
        }

        .manada-v1-button, 
        .manada-v1-add-to-cart, 
        .manada-v1-submit-button, 
        .manada-v1-newsletter-button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .manada-v1-button:hover, 
        .manada-v1-add-to-cart:hover, 
        .manada-v1-submit-button:hover, 
        .manada-v1-newsletter-button:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Hero Section */
        .manada-v1-hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://via.placeholder.com/1200x600');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 120px 20px;
            border-radius: 10px;
            margin-bottom: 60px;
        }

        .manada-v1-hero-title {
            font-size: 3.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .manada-v1-hero-subtitle {
            font-size: 1.8rem;
            margin-bottom: 20px;
            font-weight: 300;
        }

        .manada-v1-hero-description {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        /* How It Works Section */
        .manada-v1-how-it-works {
            padding: 60px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-bottom: 60px;
        }

        .manada-v1-steps-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .manada-v1-step {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            transition: transform 0.3s ease;
        }

        .manada-v1-step:hover {
            transform: translateY(-10px);
        }

        .manada-v1-step-number {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ff6b6b;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .manada-v1-step-content {
            text-align: center;
            margin-top: 15px;
        }

        .manada-v1-step h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .manada-v1-step p {
            color: #666;
            line-height: 1.6;
        }

        /* Featured Collection */
        .manada-v1-featured-collection {
            padding: 60px 20px;
            margin-bottom: 60px;
        }

        .manada-v1-featured-description {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 40px;
            font-size: 1.1rem;
            color: #666;
        }

        .manada-v1-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .manada-v1-product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .manada-v1-product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .manada-v1-product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }

        .manada-v1-product-image {
            height: 250px;
            overflow: hidden;
        }

        .manada-v1-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .manada-v1-product-card:hover .manada-v1-product-image img {
            transform: scale(1.05);
        }

        .manada-v1-product-info {
            padding: 20px;
            text-align: center;
        }

        .manada-v1-product-title {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: #333;
        }

        .manada-v1-product-price {
            font-weight: bold;
            font-size: 1.3rem;
            color: #ff6b6b;
            margin-bottom: 15px;
        }

        .manada-v1-add-to-cart {
            width: 100%;
            margin-bottom: 10px;
        }

        .manada-v1-product-details {
            display: block;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 10px;
            transition: color 0.3s ease;
        }

        .manada-v1-product-details:hover {
            color: #ff6b6b;
        }

        /* Custom Order Section */
        .manada-v1-custom-order {
            padding: 60px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-bottom: 60px;
        }

        .manada-v1-custom-order-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 40px;
        }

        .manada-v1-custom-order-image {
            flex: 1;
            min-width: 300px;
        }

        .manada-v1-custom-order-image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .manada-v1-custom-order-form {
            flex: 1;
            min-width: 300px;
        }

        .manada-v1-custom-order-form h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: #333;
        }

        .manada-v1-custom-order-form p {
            margin-bottom: 25px;
            color: #666;
            line-height: 1.6;
        }

        .manada-v1-form-group {
            margin-bottom: 20px;
        }

        .manada-v1-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .manada-v1-form-group input,
        .manada-v1-form-group select,
        .manada-v1-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .manada-v1-form-group input:focus,
        .manada-v1-form-group select:focus,
        .manada-v1-form-group textarea:focus {
            border-color: #ff6b6b;
            outline: none;
        }

        .manada-v1-submit-button {
            width: 100%;
            margin-top: 10px;
        }

        /* Testimonials */
        .manada-v1-testimonials {
            padding: 60px 20px;
            margin-bottom: 60px;
            position: relative;
        }

        .manada-v1-testimonials-slider {
            display: flex;
            overflow: hidden;
            position: relative;
        }

        .manada-v1-testimonial {
            flex: 0 0 100%;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .manada-v1-testimonial-content {
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
            line-height: 1.6;
        }

        .manada-v1-testimonial-author {
            display: flex;
            align-items: center;
        }

        .manada-v1-testimonial-author img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }

        .manada-v1-author-info h4 {
            margin: 0 0 5px;
            color: #333;
        }

        .manada-v1-stars {
            color: #ffc107;
        }

        .manada-v1-slider-controls {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .manada-v1-slider-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1.2rem;
        }

        .manada-v1-slider-btn:hover {
            background-color: #ff5252;
        }

        /* FAQ Section */
        .manada-v1-faq {
            padding: 60px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin-bottom: 60px;
        }

        .manada-v1-accordion {
            max-width: 800px;
            margin: 0 auto;
        }

        .manada-v1-accordion-item {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .manada-v1-accordion-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background-color: white;
            transition: background-color 0.3s ease;
        }

        .manada-v1-accordion-header:hover {
            background-color: #f5f5f5;
        }

        .manada-v1-accordion-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .manada-v1-accordion-icon {
            font-size: 1.5rem;
            color: #ff6b6b;
            transition: transform 0.3s ease;
        }

        .manada-v1-accordion-item.active .manada-v1-accordion-icon {
            transform: rotate(45deg);
        }

        .manada-v1-accordion-content {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }

        .manada-v1-accordion-item.active .manada-v1-accordion-content {
            padding: 0 20px 20px;
            max-height: 200px;
        }

        .manada-v1-accordion-content p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }

        /* Newsletter */
        .manada-v1-newsletter {
            padding: 60px 20px;
            margin-bottom: 60px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://via.placeholder.com/1200x400');
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            color: white;
            text-align: center;
        }

        .manada-v1-newsletter-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .manada-v1-newsletter h2 {
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .manada-v1-newsletter p {
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .manada-v1-newsletter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .manada-v1-newsletter-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
        }

        .manada-v1-newsletter-button {
            min-width: 150px;
        }

        /* Contact Info */
        .manada-v1-contact {
            padding: 60px 20px;
            margin-bottom: 60px;
        }

        .manada-v1-contact-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }

        .manada-v1-contact-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .manada-v1-contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .manada-v1-icon {
            font-size: 1.5rem;
        }

        .manada-v1-social-media {
            display: flex;
            gap: 15px;
        }

        .manada-v1-social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #ff6b6b;
            color: white;
            border-radius: 50%;
            text-decoration: none;
            font-size: 1.2rem;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .manada-v1-social-icon:hover {
            transform: translateY(-5px);
            background-color: #ff5252;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .manada-v1-hero-title {
                font-size: 2.5rem;
            }
            
            .manada-v1-hero-subtitle {
                font-size: 1.3rem;
            }
            
            .manada-v1-section-title {
                font-size: 1.8rem;
            }
            
            .manada-v1-custom-order-container {
                flex-direction: column;
            }
            
            .manada-v1-newsletter-form {
                flex-direction: column;
            }
            
            .manada-v1-newsletter-button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .manada-v1-hero {
                padding: 80px 20px;
            }
            
            .manada-v1-hero-title {
                font-size: 2rem;
            }
            
            .manada-v1-hero-subtitle {
                font-size: 1.1rem;
            }
            
            .manada-v1-section-title {
                font-size: 1.5rem;
            }
            
            .manada-v1-step {
                min-width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manada_v1_container = document.querySelector('.manada-v1-ecommerce-container');
            
            // Accordion functionality
            const manada_v1_accordionItems = manada_v1_container.querySelectorAll('.manada-v1-accordion-item');
            
            manada_v1_accordionItems.forEach(item => {
                const header = item.querySelector('.manada-v1-accordion-header');
                
                header.addEventListener('click', () => {
                    // Close all other items
                    manada_v1_accordionItems.forEach(otherItem => {
                        if (otherItem !== item && otherItem.classList.contains('active')) {
                            otherItem.classList.remove('active');
                        }
                    });
                    
                    // Toggle current item
                    item.classList.toggle('active');
                });
            });
            
            // Testimonial slider
            const manada_v1_slider = manada_v1_container.querySelector('#manada-v1-testimonials-slider');
            const manada_v1_slides = manada_v1_slider.querySelectorAll('.manada-v1-testimonial');
            const manada_v1_prevBtn = manada_v1_container.querySelector('#manada-v1-prev-btn');
            const manada_v1_nextBtn = manada_v1_container.querySelector('#manada-v1-next-btn');
            
            let manada_v1_currentSlide = 0;
            const manada_v1_slideCount = manada_v1_slides.length;
            
            function manada_v1_showSlide(index) {
                if (index < 0) index = manada_v1_slideCount - 1;
                if (index >= manada_v1_slideCount) index = 0;
                
                manada_v1_currentSlide = index;
                
                manada_v1_slides.forEach((slide, i) => {