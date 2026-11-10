/**
 * WhatsApp Integration for Brèagha Booking System
 * Handles sharing booking details via WhatsApp
 */

class WhatsAppIntegration {
    constructor(clinicPhone = '201001234567') {
        // Format: 20 + 1001234567 (without + sign)
        this.clinicPhone = clinicPhone;
        this.baseUrl = 'https://wa.me/';
    }

    /**
     * Generate WhatsApp message from booking data
     */
    generateMessage(bookingData) {
        const message = `
*براجيا - تأكيد الحجز* ✨

*بيانات المريض:*
👤 الاسم: ${bookingData.name}
📱 الهاتف: ${bookingData.phone}
🎂 العمر: ${bookingData.age} سنة

*تفاصيل الخدمة:*
🏥 الخدمة: ${bookingData.service}
💉 عدد الجلسات: ${bookingData.sessions}
📅 التاريخ: ${this.formatDate(bookingData.date)}
⏰ الوقت: ${bookingData.time}

*الأسعار:*
💰 السعر الأساسي: ${bookingData.basePrice} ج.م
${bookingData.discount > 0 ? `🎉 خصم: -${bookingData.discount} ج.م` : ''}
💵 الإجمالي: ${bookingData.totalPrice} ج.م

*معرف الحجز:* ${bookingData.bookingId}

---
شكراً لاختيارك عيادة براجيا 💎
سنتواصل معك قريباً لتأكيد الموعد
        `.trim();

        return encodeURIComponent(message);
    }

    /**
     * Format date to Arabic format
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        const formatter = new Intl.DateTimeFormat('ar-EG', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        return formatter.format(date);
    }

    /**
     * Open WhatsApp with booking message
     */
    sendViaWhatsApp(bookingData) {
        const message = this.generateMessage(bookingData);
        const whatsappUrl = `${this.baseUrl}${this.clinicPhone}?text=${message}`;
        
        // Open in new window
        window.open(whatsappUrl, '_blank', 'width=800,height=600');
    }

    /**
     * Generate WhatsApp share button HTML
     */
    createShareButton(bookingData, buttonText = 'إرسال عبر WhatsApp 💬') {
        const button = document.createElement('button');
        button.className = 'whatsapp-share-button';
        button.innerHTML = `
            <svg class="whatsapp-icon" viewBox="0 0 24 24" width="20" height="20">
                <path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-4.768 1.388c-1.454.814-2.763 1.928-3.783 3.235C2.25 10.854 1.61 12.613 1.61 14.5c0 1.803.424 3.583 1.224 5.18l-1.3 4.757 4.9-1.289c1.5.823 3.18 1.25 4.9 1.25 5.43 0 9.868-4.438 9.868-9.868 0-2.631-.997-5.109-2.813-6.976-1.816-1.867-4.297-2.895-6.827-2.895"/>
            </svg>
            ${buttonText}
        `;
        
        button.onclick = () => this.sendViaWhatsApp(bookingData);
        
        return button;
    }

    /**
     * Create an image-based booking card for sharing
     */
    async generateBookingCardImage(bookingData) {
        // This would use canvas to create an image
        // For now, returning a placeholder
        return this.createBookingCardHTML(bookingData);
    }

    /**
     * Create booking card HTML (can be converted to image)
     */
    createBookingCardHTML(bookingData) {
        const discount = bookingData.discount > 0 ? `<p class="discount">🎉 خصم: ${bookingData.discount}</p>` : '';
        
        return `
            <div class="booking-card">
                <div class="booking-card-header">
                    <h2>Brèagha</h2>
                    <p class="booking-card-subtitle">تأكيد الحجز</p>
                </div>
                
                <div class="booking-card-content">
                    <div class="booking-card-section">
                        <h3>بيانات المريض</h3>
                        <p><strong>الاسم:</strong> ${bookingData.name}</p>
                        <p><strong>الهاتف:</strong> ${bookingData.phone}</p>
                        <p><strong>العمر:</strong> ${bookingData.age}</p>
                    </div>
                    
                    <div class="booking-card-section">
                        <h3>تفاصيل الحجز</h3>
                        <p><strong>الخدمة:</strong> ${bookingData.service}</p>
                        <p><strong>الجلسات:</strong> ${bookingData.sessions}</p>
                        <p><strong>التاريخ:</strong> ${bookingData.date}</p>
                        <p><strong>الوقت:</strong> ${bookingData.time}</p>
                    </div>
                    
                    <div class="booking-card-section">
                        <h3>الأسعار</h3>
                        ${discount}
                        <p class="total"><strong>الإجمالي:</strong> ${bookingData.totalPrice} ج.م</p>
                    </div>
                    
                    <div class="booking-card-section">
                        <p class="booking-id">معرف الحجز: ${bookingData.bookingId}</p>
                    </div>
                </div>
                
                <div class="booking-card-footer">
                    <p>شكراً لاختيارك عيادة براجيا 💎</p>
                    <p>سنتواصل معك قريباً</p>
                </div>
            </div>
        `;
    }
}

// Initialize WhatsApp integration
window.whatsappIntegration = new WhatsAppIntegration('201001234567'); // Replace with clinic number

// CSS for booking card (added dynamically)
const bookingCardCSS = `
    <style>
        .booking-card {
            max-width: 400px;
            background: linear-gradient(135deg, #fff9f0, #ffffff);
            border: 2px solid #C39E31;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            font-family: 'Cairo', sans-serif;
            direction: rtl;
        }
        
        .booking-card-header {
            border-bottom: 2px solid #C39E31;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        
        .booking-card-header h2 {
            color: #C39E31;
            font-size: 1.8rem;
            margin: 0;
        }
        
        .booking-card-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 4px 0 0 0;
        }
        
        .booking-card-content {
            margin: 16px 0;
            text-align: right;
        }
        
        .booking-card-section {
            margin: 12px 0;
            padding: 12px;
            background: #faf9f6;
            border-radius: 8px;
        }
        
        .booking-card-section h3 {
            color: #C39E31;
            font-size: 0.95rem;
            margin: 0 0 8px 0;
            font-weight: 700;
        }
        
        .booking-card-section p {
            margin: 4px 0;
            color: #333;
            font-size: 0.85rem;
        }
        
        .discount {
            color: #C39E31;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .total {
            color: #C39E31;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .booking-id {
            font-size: 0.8rem;
            color: #999;
        }
        
        .booking-card-footer {
            border-top: 2px solid #C39E31;
            padding-top: 16px;
            margin-top: 16px;
        }
        
        .booking-card-footer p {
            color: #C39E31;
            font-weight: 600;
            margin: 4px 0;
        }
    </style>
`;

// Inject CSS
if (!document.querySelector('style[data-booking-card]')) {
    const styleElement = document.createElement('style');
    styleElement.setAttribute('data-booking-card', 'true');
    styleElement.textContent = bookingCardCSS.replace(/<style>|<\/style>/g, '');
    document.head.appendChild(styleElement);
}

// WhatsApp button styles
const whatsappButtonCSS = `
    <style>
        .whatsapp-share-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #25d366, #20ba5a);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
        }
        
        .whatsapp-share-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.3);
        }
        
        .whatsapp-share-button:active {
            transform: translateY(0);
        }
        
        .whatsapp-icon {
            width: 20px;
            height: 20px;
        }
    </style>
`;

// Inject WhatsApp button styles
if (!document.querySelector('style[data-whatsapp-btn]')) {
    const styleElement = document.createElement('style');
    styleElement.setAttribute('data-whatsapp-btn', 'true');
    styleElement.textContent = whatsappButtonCSS.replace(/<style>|<\/style>/g, '');
    document.head.appendChild(styleElement);
}
