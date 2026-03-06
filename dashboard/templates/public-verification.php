<?php if (!defined('ABSPATH')) exit; ?>
<div class="dashboard-verify-container" dir="rtl">
    <div class="dashboard-verify-header">
        <h2 style="font-weight: 800; color: var(--dashboard-dark-color); margin-bottom: 10px;">محرك التحقق الرسمي</h2>
        <p style="color: #64748b; font-size: 14px;">قم بالتحقق من صحة وصلاحية المستندات والعضويات الرسمية الصادرة عن Dashboard.</p>
    </div>

    <div class="dashboard-verify-search-box">
        <form id="dashboard-verify-form">
            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: flex-end;">
                <div class="dashboard-form-group" style="margin-bottom: 0;">
                    <label class="dashboard-label">نوع البحث:</label>
                    <select id="dashboard-verify-type" class="dashboard-select" style="background: #fff;">
                        <option value="all">اسم المستخدم</option>
                        <option value="membership">رقم العضوية</option>
                        <option value="license">رقم رخصة المنشأة</option>
                        <option value="practice">رقم تصريح المزاولة</option>
                    </select>
                </div>
                <div class="dashboard-form-group" style="margin-bottom: 0;">
                    <label class="dashboard-label">قيمة البحث:</label>
                    <input type="text" id="dashboard-verify-value" class="dashboard-input" placeholder="أدخل الرقم المراد التحقق منه..." style="background: #fff;">
                </div>
                <button type="submit" class="dashboard-btn" style="height: 45px; padding: 0 30px; font-weight: 700;">تحقق الآن</button>
            </div>
        </form>
    </div>

    <div id="dashboard-verify-loading" style="display: none; text-align: center; padding: 40px;">
        <span class="dashicons dashicons-update spin" style="font-size: 30px; color: var(--dashboard-primary-color); width: 30px; height: 30px;"></span>
        <p style="margin-top: 10px; color: #64748b;">جاري استعلام البيانات من قاعدة البيانات...</p>
    </div>

    <div id="dashboard-verify-results" style="margin-top: 30px;"></div>
</div>

<style>
/* Verification styles handled in dashboard-public.css */
#dashboard-verify-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 150px;
}
</style>

<script>
(function($) {
    $('#dashboard-verify-form').on('submit', function(e) {
        e.preventDefault();
        const val = $('#dashboard-verify-value').val();
        const type = $('#dashboard-verify-type').val();
        const results = $('#dashboard-verify-results').empty();
        const loading = $('#dashboard-verify-loading').show();

        const fd = new FormData();
        fd.append('action', 'dashboard_verify_document');
        fd.append('search_value', val);
        fd.append('search_type', type);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            loading.hide();
            if (res.success) {
                renderResults(res.data);
            } else {
                results.append(`<div style="background: #fff5f5; color: #c53030; padding: 20px; border-radius: 10px; border: 1px solid #feb2b2; text-align: center; font-weight: 600;">${res.data}</div>`);
            }
        });
    });

    function renderResults(data) {
        const results = $('#dashboard-verify-results');
        const today = new Date();

        for (let k in data) {
            const doc = data[k];
            let statusClass = 'dashboard-verify-status-valid';
            let statusLabel = 'صالح / ساري';

            if (doc.expiry) {
                const expiry = new Date(doc.expiry);
                if (expiry < today) {
                    statusClass = 'dashboard-verify-status-invalid';
                    statusLabel = 'منتهي الصلاحية';
                }
            }

            let html = `
                <div class="dashboard-verify-card">
                    <div class="dashboard-verify-card-header">
                        <h3 style="margin: 0; font-weight: 800; color: var(--dashboard-primary-color); font-size: 1.1em;">${doc.label}</h3>
                        <span class="dashboard-badge ${statusClass === 'dashboard-verify-status-valid' ? 'dashboard-badge-high' : 'dashboard-badge-urgent'}" style="font-size: 11px;">${statusLabel}</span>
                    </div>
                    <div class="dashboard-verify-grid">
            `;

            if (k === 'membership') {
                html += `
                    <div class="dashboard-verify-item"><label>الاسم</label><span>${doc.name}</span></div>
                    <div class="dashboard-verify-item"><label>رقم القيد</label><span>${doc.number}</span></div>
                    <div class="dashboard-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            } else if (k === 'license') {
                html += `
                    <div class="dashboard-verify-item"><label>اسم المنشأة</label><span>${doc.facility_name}</span></div>
                    <div class="dashboard-verify-item"><label>رقم الرخصة</label><span>${doc.number}</span></div>
                    <div class="dashboard-verify-item"><label>الفئة</label><span>${doc.category}</span></div>
                    <div class="dashboard-verify-item"><label>العنوان</label><span>${doc.address}</span></div>
                    <div class="dashboard-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            } else if (k === 'practice') {
                html += `
                    <div class="dashboard-verify-item"><label>اسم صاحب التصريح</label><span>${doc.name}</span></div>
                    <div class="dashboard-verify-item"><label>رقم التصريح</label><span>${doc.number}</span></div>
                    <div class="dashboard-verify-item"><label>تاريخ الإصدار</label><span>${doc.issue_date}</span></div>
                    <div class="dashboard-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            }

            html += `</div></div>`;
            results.append(html);
        }
    }
})(jQuery);
</script>
