/**
 * Brèagha Enhanced Booking Modal System
 * Service-based booking with auto-fill and WhatsApp confirmation
 */

class BookingModal {
    constructor() {
        this.modal = null;
        this.isOpen = false;
        this.selectedService = '';
        this.bookingData = {
            fullName: '',
            phone: '',
            service: '',
            sessions: 1,
            offerEligible: false
        };
        this.init();
    }

    init() {
        this.createModalHTML();
        this.attachEventListeners();
    }

    createModalHTML() {
        const modalHTML = `
            <div id="bookingModal" class="booking-modal" role="dialog" aria-hidden="true" aria-labelledby="bookingModalTitle">
                <div class="booking-modal-overlay"></div>
                <div class="booking-modal-content">
                    <!-- Close Button -->
                    <button class="booking-modal-close" aria-label="إغلاق النموذج">
                        <span>✕</span>
                    </button>

                    <!-- Modal Header -->
                    <div class="booking-modal-header">
                        <h2 id="bookingModalTitle">احجز موعدك الآن</h2>
                        <p class="booking-modal-subtitle">تجربة حجز سلسة واحترافية</p>
                    </div>

                    <!-- Form Container -->
                    <form id="bookingForm" class="booking-modal-form">
                        <!-- Service Selection (Auto-filled) -->
                        <div class="form-group-booking">
                            <label for="service">الخدمة المختارة *</label>
                            <input
                                type="text"
                                id="service"
                                name="service"
                                placeholder="سيتم تحديدها تلقائياً"
                                required
                                class="input-with-icon"
                                readonly
                            >
                            <span class="input-icon">🎯</span>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-group-booking">
                            <label for="fullName">الاسم الكامل *</label>
                            <input
                                type="text"
                                id="fullName"
                                name="fullName"
                                placeholder="أدخل اسمك الكامل"
                                required
                                class="input-with-icon"
                            >
                            <span class="input-icon">👤</span>
                        </div>

                        <div class="form-group-booking">
                            <label for="phone">رقم الهاتف *</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="01XXXXXXXXX"
                                required
                                pattern="[0-9]{11}"
                                class="input-with-icon"
                            >
                            <span class="input-icon">📱</span>
                        </div>

                        <!-- Number of Sessions -->
                        <div class="form-group-booking">
                            <label for="sessions">عدد الجلسات *</label>
                            <select id="sessions" name="sessions" required class="input-with-icon">
                                <option value="1">جلسة واحدة</option>
                                <option value="2">جلستان</option>
                                <option value="3">3 جلسات (عرض خاص)</option>
                                <option value="4">4 جلسات</option>
                                <option value="5">5 جلسات</option>
                                <option value="6">6 جلسات</option>
                            </select>
                            <span class="input-icon">🔢</span>
                        </div>

                        <!-- Offer Eligibility Notice -->
                        <div id="offerNotice" class="offer-notice" style="display: none;">
                            <div class="offer-badge">🎉 عرض خاص متاح!</div>
                            <p>باقة 3 جلسات أو أكثر مؤهلة للعروض الخاصة</p>
                        </div>

                        <!-- Terms Agreement -->
                        <div class="terms-agreement">
                            <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                            <label for="agreeTerms">أوافق على سياسة الخصوصية والشروط والأحكام</label>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="booking-buttons">
                            <button type="button" class="btn-booking btn-secondary-booking" id="cancelBtn">
                                إلغاء
                            </button>
                            <button type="submit" class="btn-booking btn-primary-booking btn-submit-booking" id="submitBtn">
                                <span class="submit-text">إرسال الحجز</span>
                                <span class="submit-loader hidden"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        // Inject modal into the DOM
        if (!document.getElementById('bookingModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            this.modal = document.getElementById('bookingModal');
        }
    }

    attachEventListeners() {
        // Attach form submission
        const form = document.getElementById('bookingForm');
        form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Attach close button
        const closeBtn = document.querySelector('.booking-modal-close');
        closeBtn.addEventListener('click', () => this.close());

        // Attach cancel button
        const cancelBtn = document.getElementById('cancelBtn');
        cancelBtn.addEventListener('click', () => this.close());

        // Attach overlay click
        const overlay = document.querySelector('.booking-modal-overlay');
        overlay.addEventListener('click', () => this.close());

        // Attach sessions change
        const sessionsSelect = document.getElementById('sessions');
        sessionsSelect.addEventListener('change', () => this.updateOfferEligibility());
    }

    open(serviceName = '') {
        // Set service if provided
        if (serviceName) {
            this.setService(serviceName);
        }

        this.modal.classList.add('active');
        this.modal.setAttribute('aria-hidden', 'false');
        this.isOpen = true;
        document.body.style.overflow = 'hidden';

        // Trigger animation
        setTimeout(() => {
            this.modal.classList.add('show');
        }, 10);
    }

    close() {
        this.modal.classList.remove('show');
        this.isOpen = false;
        document.body.style.overflow = '';

        setTimeout(() => {
            this.modal.classList.remove('active');
            this.resetForm();
        }, 300);
    }

    setService(serviceName) {
        this.selectedService = serviceName;
        const serviceInput = document.getElementById('service');
        if (serviceInput) {
            serviceInput.value = serviceName;
        }
    }

    updateOfferEligibility() {
        const sessionsSelect = document.getElementById('sessions');
        const offerNotice = document.getElementById('offerNotice');
        const sessions = parseInt(sessionsSelect.value);

        this.bookingData.offerEligible = sessions >= 3;

        if (this.bookingData.offerEligible) {
            offerNotice.style.display = 'block';
        } else {
            offerNotice.style.display = 'none';
        }
    }

    async handleSubmit(event) {
        event.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const submitText = submitBtn.querySelector('.submit-text');
        const submitLoader = submitBtn.querySelector('.submit-loader');

        // Show loading state
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        submitLoader.classList.remove('hidden');

        // Collect form data
        this.bookingData.fullName = document.getElementById('fullName').value;
        this.bookingData.phone = document.getElementById('phone').value;
        this.bookingData.service = document.getElementById('service').value;
        this.bookingData.sessions = parseInt(document.getElementById('sessions').value);
        this.bookingData.offerEligible = this.bookingData.sessions >= 3;

        const formData = new FormData();
        formData.append('name', this.bookingData.fullName);
        formData.append('phone', this.bookingData.phone);
        formData.append('service', this.bookingData.service);
        formData.append('sessions', this.bookingData.sessions);
        formData.append('offer_eligible', this.bookingData.offerEligible ? '1' : '0');

        const csrfContainer = document.getElementById('csrf-token-container');
        if (csrfContainer) {
            formData.append('csrf_token', csrfContainer.getAttribute('data-csrf') || '');
        }

        try {
            const response = await fetch('api/booking-api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            // Refresh CSRF token dynamically on the page
            if (result.csrf_token) {
                if (csrfContainer) csrfContainer.setAttribute('data-csrf', result.csrf_token);
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = result.csrf_token;
                });
            }

            if (result.success) {
                // Show success animation
                this.showSuccessAnimation(result.bookingId);
            } else {
                alert('حدث خطأ: ' + result.message);
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitLoader.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال: ' + error.message);
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            submitLoader.classList.add('hidden');
        }
    }

    showSuccessAnimation(bookingId) {
        const modal = document.querySelector('.booking-modal-content');

        const successHTML = `
            <div class="booking-success-animation">
                <div class="success-checkmark">
                    <svg viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="26" cy="26" r="25" fill="none" stroke="#C39E31" stroke-width="2"/>
                        <path fill="none" stroke="#C39E31" stroke-width="3" stroke-linecap="round" d="M14.1 27.2l7.1 7.2 16.7-16.6"/>
                    </svg>
                </div>
                <h3>تم الحجز بنجاح! ✨</h3>
                <p>جاري إعداد ملخص الحجز...</p>
            </div>
        `;

        modal.innerHTML = successHTML;

        // Generate booking summary after a short delay
        setTimeout(() => {
            this.generateBookingSummary(bookingId);
        }, 1500);
    }

    async generateBookingSummary(bookingId) {
        const modal = document.querySelector('.booking-modal-content');

        const summaryHTML = `
            <div class="booking-summary">
                <div class="summary-header">
                    <h3>ملخص الحجز</h3>
                    <div class="booking-id">رقم الحجز: ${bookingId}</div>
                </div>

                <div class="summary-details">
                    <div class="summary-item">
                        <span class="summary-label">الاسم:</span>
                        <span class="summary-value">${this.bookingData.fullName}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">الهاتف:</span>
                        <span class="summary-value">${this.bookingData.phone}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">الخدمة:</span>
                        <span class="summary-value">${this.bookingData.service}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">عدد الجلسات:</span>
                        <span class="summary-value">${this.bookingData.sessions}</span>
                    </div>
                    ${this.bookingData.offerEligible ? `<div class="summary-item"><span class="offer-badge-large">🎉 مؤهل للعروض</span></div>` : ''}
                </div>

                <div class="whatsapp-section">
                    <p>اضغط أدناه لتأكيد الموعد عبر WhatsApp</p>
                    <button class="whatsapp-confirm-btn" onclick="bookingModal.confirmViaWhatsApp()">
                        <span class="whatsapp-icon">💬</span>
                        تأكيد الموعد عبر WhatsApp
                    </button>
                </div>
            </div>
        `;

        modal.innerHTML = summaryHTML;
    }

    confirmViaWhatsApp() {
        const message = `
مرحباً، أنا بصدد تأكيد الحجز:

الاسم: ${this.bookingData.fullName}
الهاتف: ${this.bookingData.phone}
الخدمة: ${this.bookingData.service}
عدد الجلسات: ${this.bookingData.sessions}
${this.bookingData.offerEligible ? 'مؤهل للعروض الخاصة: نعم' : ''}

يرجى تأكيد الموعد والتفاصيل.
        `;

        const whatsappUrl = `https://wa.me/201001234567?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');

        // Close modal after WhatsApp opens
        setTimeout(() => {
            this.close();
        }, 500);
    }

    resetForm() {
        const form = document.getElementById('bookingForm');
        if (form) {
            form.reset();
            document.getElementById('offerNotice').style.display = 'none';
        }

        // Reset data
        this.bookingData = {
            fullName: '',
            phone: '',
            service: '',
            sessions: 1,
            offerEligible: false
        };
    }
}

// Initialize booking modal when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.bookingModal === 'undefined') {
        window.bookingModal = new BookingModal();
    }
});

// Global function to open booking modal from anywhere
function openBookingModal(serviceName = '') {
    if (typeof window.bookingModal !== 'undefined') {
        window.bookingModal.open(serviceName);
    }
}
