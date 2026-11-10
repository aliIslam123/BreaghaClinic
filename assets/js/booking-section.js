'use strict';

const LASER_AREAS = [
    'جسم كامل', 'وجه', 'بكيني + اندر أرم', 'بكيني',
    'رجلين كاملين', 'نصف رجلين', 'ذراعين كاملين', 'نصف ذراعين',
];

const DEPT_LABELS = {};

const ARABIC_DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
const ARABIC_MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

const DAY_MAP = { 'saturday': 6, 'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3, 'thursday': 4, 'friday': 5 };
const DAY_MAP_REV = { 0: 'sunday', 1: 'monday', 2: 'tuesday', 3: 'wednesday', 4: 'thursday', 5: 'friday', 6: 'saturday' };

let deptScheduleCache = null;
let currentGender = '';

const $ = id => document.getElementById(id);

function showError(fieldId, msg) {
    const el = $(`${fieldId}-err`);
    const input = $(fieldId);
    if (el) { el.textContent = msg; el.classList.add('visible'); }
    if (input) input.classList.add('error');
}

function clearError(fieldId) {
    const el = $(`${fieldId}-err`);
    const input = $(fieldId);
    if (el) { el.textContent = ''; el.classList.remove('visible'); }
    if (input) input.classList.remove('error');
}

function clearAllErrors() {
    ['bf-full_name', 'bf-phone', 'bf-age', 'bf-gender', 'bf-department', 'bf-booking_type', 'bf-service',
        'bf-laser_area', 'bf-sessions', 'bf-booking_date', 'bf-booking_time'].forEach(clearError);
    const alert = $('bf-global-alert');
    if (alert) { alert.className = 'bf-global-alert'; alert.textContent = ''; }
}

function showGlobalAlert(msg, type = 'error') {
    const alert = $('bf-global-alert');
    if (!alert) return;
    alert.textContent = msg;
    alert.className = `bf-global-alert alert-${type}`;
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/* ═══════════════════════════════════════════════════════════
   DYNAMIC: Load departments with gender-specific schedule from API
   ═══════════════════════════════════════════════════════════ */
async function loadDeptScheduleCache(gender) {
    try {
        const genderParam = (gender === 'ذكر') ? 'male' : (gender === 'أنثى') ? 'female' : '';
        const url = genderParam ? `api/departments.php?gender=${genderParam}` : 'api/departments.php';
        const res = await fetch(url);
        if (!res.ok) throw new Error('Network error');
        deptScheduleCache = await res.json();
        return deptScheduleCache;
    } catch (e) {
        console.error('Failed to load department schedules:', e);
        return [];
    }
}

function getDeptSchedule(slug) {
    if (!deptScheduleCache) return null;
    return deptScheduleCache.find(d => d.slug === slug) || null;
}

function getDeptWorkingDays(slug) {
    const dept = getDeptSchedule(slug);
    if (!dept) return [0, 1, 2, 3, 4, 5, 6];
    let days = dept.working_days || [];
    return days.map(d => DAY_MAP[d]).filter(d => d !== undefined);
}

function getDeptTimeRange(slug) {
    const dept = getDeptSchedule(slug);
    if (!dept) return { start: 10, end: 23 };
    const startHour = parseInt((dept.start_time || '10:00:00').split(':')[0]);
    const endHour = parseInt((dept.end_time || '23:00:00').split(':')[0]);
    return { start: startHour, end: endHour };
}

async function loadServicesFromAPI(dept) {
    try {
        const url = dept ? `api/services.php?department=${encodeURIComponent(dept)}` : 'api/services.php';
        const res = await fetch(url);
        if (!res.ok) throw new Error('Network error');
        return await res.json();
    } catch (e) {
        console.error('Failed to load services:', e);
        return [];
    }
}

async function loadOffersFromAPI(dept) {
    try {
        const url = dept ? `api/offers.php?department=${encodeURIComponent(dept)}` : 'api/offers.php';
        const res = await fetch(url);
        if (!res.ok) throw new Error('Network error');
        return await res.json();
    } catch (e) {
        console.error('Failed to load offers:', e);
        return [];
    }
}

async function populateServicesOrOffers(dept, type) {
    const sel = $('bf-service');
    const label = $('bf-service-label');
    if (!sel) return;

    const priceInput = $('bf-price');
    if (priceInput) priceInput.value = '';

    if (!dept) {
        sel.innerHTML = '<option value="">-- اختر القسم أولاً --</option>';
        sel.disabled = true;
        if (label) label.innerHTML = 'الخدمة <span class="req">*</span>';
        return;
    }

    if (!type) {
        sel.innerHTML = '<option value="">-- اختر نوع الحجز أولاً --</option>';
        sel.disabled = true;
        if (label) label.innerHTML = 'الخدمة / العرض <span class="req">*</span>';
        return;
    }

    if (label) {
        label.innerHTML = (type === 'offer' ? 'العرض' : 'الخدمة') + ' <span class="req">*</span>';
    }

    sel.innerHTML = '<option value="">-- جاري التحميل... --</option>';
    sel.disabled = true;

    try {
        if (type === 'offer') {
            const offers = await loadOffersFromAPI(dept);
            sel.innerHTML = '<option value="">-- اختر العرض --</option>';
            if (offers.length === 0) {
                sel.innerHTML = '<option value="">-- لا توجد عروض متاحة في هذا القسم --</option>';
            } else {
                offers.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.name;
                    opt.textContent = `${o.name} (${parseFloat(o.price)} ج.م)`;
                    opt.setAttribute('data-price', o.price);
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            }
        } else {
            const services = await loadServicesFromAPI(dept);
            sel.innerHTML = '<option value="">-- اختر الخدمة --</option>';
            if (services.length === 0) {
                sel.innerHTML = '<option value="">-- لا توجد خدمات متاحة في هذا القسم --</option>';
            } else {
                services.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.name;
                    opt.textContent = s.name;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            }
        }
    } catch (e) {
        console.error(e);
        sel.innerHTML = '<option value="">-- فشل التحميل --</option>';
    }

    clearError('bf-service');
    const gender = $('bf-gender')?.value || '';
    populateDates(dept, gender);
    populateTimes(dept, gender);
}

function toggleLaserArea(service) {
    const wrapper = $('laser-area-wrapper');
    const select = $('bf-laser_area');
    if (!wrapper || !select) return;
    if (service === 'إزالة الشعر بالليزر' || service === 'جلسات إزالة الشعر بالليزر') {
        wrapper.classList.add('visible');
        select.required = true;
    } else {
        wrapper.classList.remove('visible');
        select.required = false;
        select.value = '';
        clearError('bf-laser_area');
    }
}

function populateDates(dept, gender) {
    const sel = $('bf-booking_date');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- اختر التاريخ --</option>';

    const deptSchedule = getDeptSchedule(dept);
    let allowedDays = [];
    if (deptSchedule) {
        const workingDays = deptSchedule.working_days || [];
        allowedDays = workingDays.map(d => DAY_MAP[d]).filter(d => d !== undefined);
    }

    if (allowedDays.length === 0) return;

    const today = new Date();
    for (let i = 1; i <= 30; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        const dayOfWeek = d.getDay();

        if (!allowedDays.includes(dayOfWeek)) continue;

        const y = d.getFullYear();
        const mo = String(d.getMonth() + 1).padStart(2, '0');
        const dy = String(d.getDate()).padStart(2, '0');
        const value = `${y}-${mo}-${dy}`;
        const label = `${ARABIC_DAYS[d.getDay()]} ${d.getDate()} ${ARABIC_MONTHS[d.getMonth()]} ${y}`;
        const o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        sel.appendChild(o);
    }
}

function populateTimes(dept, gender) {
    const sel = $('bf-booking_time');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- اختر الوقت --</option>';

    const deptSchedule = getDeptSchedule(dept);
    if (!deptSchedule) return;

    let startHour = parseInt((deptSchedule.start_time || '10:00:00').split(':')[0]);
    let endHour = parseInt((deptSchedule.end_time || '23:00:00').split(':')[0]);

    for (let h = startHour; h < endHour; h++) {
        const label = h <= 12
            ? (h === 12 ? '12:00 ظهراً' : `${h}:00 صباحاً`)
            : `${h === 13 ? 1 : h === 14 ? 2 : h === 15 ? 3 : h === 16 ? 4 : h === 17 ? 5 : h === 18 ? 6 : h === 19 ? 7 : h === 20 ? 8 : h === 21 ? 9 : h === 22 ? 10 : 11}:00 مساءً`;
        const value = `${String(h).padStart(2, '0')}:00`;
        const o = document.createElement('option');
        o.value = value;
        o.textContent = label;
        sel.appendChild(o);
    }
}

function toggleSessions(service) {
    const wrapper = $('sessions-wrapper');
    const sel = $('bf-sessions');
    if (!wrapper) return;
    if (service === 'إزالة الشعر بالليزر' || service === 'جلسات إزالة الشعر بالليزر' || service === 'جلسات الفراكشنال ليزر') {
        wrapper.classList.add('visible');
        if (sel) sel.required = true;
    } else {
        wrapper.classList.remove('visible');
        if (sel) { sel.required = false; sel.value = ''; }
        clearError('bf-sessions');
    }
}

function initBookingForm() {
    const form = $('inline-booking-form');
    if (!form) return;

    loadDeptScheduleCache().then(() => {
        populateDates();
        populateTimes();
    });

    const deptSel = $('bf-department');
    const typeSel = $('bf-booking_type');
    const serviceSel = $('bf-service');
    const genderSel = $('bf-gender');

    if (deptSel) {
        deptSel.addEventListener('change', async () => {
            const gender = genderSel ? genderSel.value : '';
            await loadDeptScheduleCache(gender);
            const dept = deptSel.value;
            if (typeSel) {
                typeSel.value = '';
                typeSel.disabled = !dept;
                clearError('bf-booking_type');
            }
            populateServicesOrOffers(dept, '');
            toggleSessions('');
            if (serviceSel) {
                serviceSel.value = '';
                toggleLaserArea('');
            }
            clearError('bf-department');
        });
    }

    if (typeSel) {
        typeSel.addEventListener('change', () => {
            const dept = deptSel ? deptSel.value : '';
            const type = typeSel.value;
            populateServicesOrOffers(dept, type);
            toggleSessions('');
            if (serviceSel) {
                serviceSel.value = '';
                toggleLaserArea('');
            }
            clearError('bf-booking_type');
        });
    }

    if (genderSel) {
        genderSel.addEventListener('change', async () => {
            const dept = deptSel ? deptSel.value : '';
            const gender = genderSel.value;
            currentGender = gender;
            if (dept) {
                await loadDeptScheduleCache(gender);
                populateDates(dept, gender);
                populateTimes(dept, gender);
            }
            clearError('bf-gender');
        });
    }

    if (serviceSel) {
        serviceSel.addEventListener('change', () => {
            const service = serviceSel.value;

            const selectedOpt = serviceSel.options[serviceSel.selectedIndex];
            const price = selectedOpt ? selectedOpt.getAttribute('data-price') : '';
            const priceInput = $('bf-price');
            if (priceInput) {
                priceInput.value = price || '';
            }

            toggleLaserArea(service);
            toggleSessions(service);
            clearError('bf-service');
        });
    }

    ['bf-full_name', 'bf-phone', 'bf-age'].forEach(id => {
        const el = $(id);
        if (el) el.addEventListener('input', () => clearError(id));
    });
    ['bf-booking_type', 'bf-booking_date', 'bf-booking_time', 'bf-sessions', 'bf-laser_area', 'bf-gender'].forEach(id => {
        const el = $(id);
        if (el) el.addEventListener('change', () => clearError(id));
    });

    const params = new URLSearchParams(window.location.search);
    const urlDept = params.get('dept');
    const urlType = params.get('type');
    const urlService = params.get('service');

    if (urlDept && deptSel) {
        deptSel.value = urlDept;
        clearError('bf-department');
        if (typeSel) {
            typeSel.disabled = false;
            if (urlType) {
                typeSel.value = urlType;
                clearError('bf-booking_type');
                populateServicesOrOffers(urlDept, urlType).then(() => {
                    if (urlService && serviceSel) {
                        serviceSel.value = urlService;
                        clearError('bf-service');

                        const selectedOpt = serviceSel.options[serviceSel.selectedIndex];
                        const price = selectedOpt ? selectedOpt.getAttribute('data-price') : '';
                        const priceInput = $('bf-price');
                        if (priceInput) {
                            priceInput.value = price || '';
                        }

                        toggleLaserArea(urlService);
                        toggleSessions(urlService);
                    }
                });
            } else {
                populateServicesOrOffers(urlDept, '');
            }
        }
    }

    form.addEventListener('submit', handleFormSubmit);
}

function validateForm() {
    clearAllErrors();
    let valid = true;

    const fullName = $('bf-full_name')?.value.trim() || '';
    if (fullName.length < 5) { showError('bf-full_name', 'الاسم الكامل مطلوب (5 أحرف على الأقل)'); valid = false; }

    const phone = $('bf-phone')?.value.trim() || '';
    if (!/^0[0-9]{10}$/.test(phone)) { showError('bf-phone', 'رقم الهاتف يجب أن يكون 11 رقماً ويبدأ بـ 0'); valid = false; }

    const age = parseInt($('bf-age')?.value || '0');
    if (age < 10 || age > 100) { showError('bf-age', 'العمر يجب أن يكون بين 10 و100 سنة'); valid = false; }

    const dept = $('bf-department')?.value || '';
    if (!dept) { showError('bf-department', 'يرجى اختيار القسم'); valid = false; }

    const gender = $('bf-gender')?.value || '';
    if (!gender) { showError('bf-gender', 'يرجى اختيار النوع'); valid = false; }

    const type = $('bf-booking_type')?.value || '';
    if (!type) { showError('bf-booking_type', 'يرجى اختيار نوع الحجز'); valid = false; }

    const service = $('bf-service')?.value || '';
    if (!service) { showError('bf-service', type === 'offer' ? 'يرجى اختيار العرض' : 'يرجى اختيار الخدمة'); valid = false; }

    const laserWrapper = $('laser-area-wrapper');
    if (laserWrapper && laserWrapper.classList.contains('visible')) {
        const area = $('bf-laser_area')?.value || '';
        if (!area) { showError('bf-laser_area', 'يرجى اختيار المنطقة'); valid = false; }
    }

    const sessWrapper = $('sessions-wrapper');
    if (sessWrapper && sessWrapper.classList.contains('visible')) {
        const sessions = $('bf-sessions')?.value || '';
        if (!sessions) { showError('bf-sessions', 'يرجى اختيار عدد الجلسات'); valid = false; }
    }

    const date = $('bf-booking_date')?.value || '';
    if (!date) { showError('bf-booking_date', 'يرجى اختيار التاريخ'); valid = false; }

    const time = $('bf-booking_time')?.value || '';
    if (!time) { showError('bf-booking_time', 'يرجى اختيار الوقت'); valid = false; }

    return valid;
}

async function handleFormSubmit(e) {
    e.preventDefault();
    if (!validateForm()) return;
    const btn = $('bf-submit-btn');
    if (!btn) return;
    btn.classList.add('loading');
    btn.disabled = true;

    const formData = new FormData($('inline-booking-form'));
    try {
        const res = await fetch('api/booking-submit.php', {
            method: 'POST',
            body: formData,
        });
        const data = await res.json();
        if (data.csrf_token) {
            const csrfContainer = document.getElementById('csrf-token-container');
            if (csrfContainer) csrfContainer.setAttribute('data-csrf', data.csrf_token);
            document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                input.value = data.csrf_token;
            });
        }
        if (data.success) {
            showSuccessCard(data);
        } else {
            if (data.field) {
                showError(data.field, data.message);
            } else {
                showGlobalAlert(data.message || 'حدث خطأ أثناء الحجز');
            }
        }
    } catch (err) {
        showGlobalAlert('فشل الاتصال بالخادم');
    } finally {
        btn.classList.remove('loading');
        btn.disabled = false;
    }
}

function showSuccessCard(data) {
    const wrap = $('booking-form-wrap');
    const card = $('bf-success-card');
    if (!wrap || !card) return;

    const setText = (id, val) => { const el = $(id); if (el) el.textContent = val || '—'; };
    setText('ss-booking-id', 'رقم الحجز: ' + (data.booking_id || '—'));
    setText('ss-name', data.details?.full_name || data.details?.name || '—');
    setText('ss-phone', data.details?.phone || '—');
    setText('ss-age', data.details?.age || '—');
    setText('ss-dept', data.details?.department || '—');

    const serviceLabel = data.details?.booking_type === 'offer' ? 'العرض' : 'الخدمة';
    const serviceCell = $('ss-service');
    if (serviceCell) {
        const row = serviceCell.closest('tr');
        if (row) {
            const labelCell = row.firstElementChild;
            if (labelCell) labelCell.textContent = serviceLabel;
        }
    }
    setText('ss-service', data.details?.service || '—');

    if (data.details?.laser_area) {
        const row = $('ss-laser-area-row');
        if (row) { row.style.display = 'table-row'; setText('ss-laser-area', data.details.laser_area); }
    } else {
        const row = $('ss-laser-area-row');
        if (row) row.style.display = 'none';
    }

    if (data.details?.sessions) {
        const row = $('ss-sessions-row');
        if (row) { row.style.display = 'table-row'; setText('ss-sessions', data.details.sessions); }
    } else {
        const row = $('ss-sessions-row');
        if (row) row.style.display = 'none';
    }

    const priceRow = $('ss-price-row');
    if (priceRow) {
        if (data.details?.price && parseFloat(data.details.price) > 0) {
            priceRow.style.display = 'table-row';
            setText('ss-price', parseFloat(data.details.price) + ' ج.م');
        } else {
            priceRow.style.display = 'none';
        }
    }

    setText('ss-date', data.details?.booking_date || data.details?.date || '—');
    setText('ss-time', data.details?.booking_time || data.details?.time || '—');

    card._bookingData = data.details || data;

    wrap.style.display = 'none';
    card.style.display = 'block';
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function resetBookingForm() {
    const wrap = $('booking-form-wrap');
    const card = $('bf-success-card');
    const form = $('inline-booking-form');
    if (wrap) wrap.style.display = 'block';
    if (card) card.style.display = 'none';
    if (form) {
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        const currentToken = csrfInput ? csrfInput.value : '';
        form.reset();
        if (csrfInput && currentToken) csrfInput.value = currentToken;
    }
    clearAllErrors();
    populateServicesOrOffers('', '');
    const typeSel = $('bf-booking_type');
    if (typeSel) typeSel.disabled = true;
    toggleLaserArea('');
    toggleSessions('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function downloadInvoice() {
    const area = document.getElementById('invoice-capture-area');
    if (!area || typeof html2canvas === 'undefined') return;
    const btn = document.getElementById('download-invoice-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'جاري التحميل...'; }
    html2canvas(area, { scale: 2, backgroundColor: '#fff' }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'تأكيد_حجز_Breagha.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> حفظ كصورة'; }
    }).catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> حفظ كصورة'; }
    });
}

function openWhatsAppConfirmation(data) {
    const phone = data?.phone || '';
    const name = data?.full_name || data?.name || '';
    const service = data?.service || '';
    const date = data?.booking_date || data?.date || '';
    const time = data?.booking_time || data?.time || '';
    const dept = data?.department || '';
    const type = data?.booking_type || 'service';
    const price = data?.price || '';

    let priceText = '';
    if (type === 'offer' && price) {
        priceText = `\n💰 السعر: ${parseFloat(price)} ج.م`;
    }
    const typeLabel = type === 'offer' ? '🎁 العرض' : '💼 الخدمة';

    let laserDetails = '';
    if (data?.laser_area) {
        laserDetails += `\n📍 المنطقة: ${data.laser_area}`;
    }
    if (data?.sessions) {
        laserDetails += `\n🔄 الجلسات: ${data.sessions}`;
    }

    const msg = `السلام عليكم،\nأريد تأكيد حجزي في Brèagha Clinic:\n👤 الاسم: ${name}\n📞 الهاتف: ${phone}\n🏥 القسم: ${dept}\n${typeLabel}: ${service}${laserDetails}${priceText}\n📅 التاريخ: ${date}\n⏰ الوقت: ${time}\n\nبرجاء تأكيد الموعد.`;
    const url = `https://wa.me/201108343753?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

/* ─── Init on DOM ready ─── */
document.addEventListener('DOMContentLoaded', initBookingForm);

/* ─── Global scroll function ─── */
function smoothScrollToBooking() {
    const el = document.getElementById('booking-section');
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
}
