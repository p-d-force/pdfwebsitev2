<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Submit</span>
            <h2 class="section-title">Share Information</h2>
            <p class="section-subtitle">Tips, documents, or data about special education practices, public records, or systemic issues.</p>
        </div>

        <div class="submit-tabs" data-animate>
            <button class="submit-tab active" onclick="showTab('tip')">Submit a Tip</button>
            <button class="submit-tab" onclick="showTab('help')">Request Help</button>
            <button class="submit-tab" onclick="showTab('upload')">Upload Documents</button>
        </div>

        <form method="POST" action="/api/submit" style="max-width:650px;margin:0 auto;" data-animate>
            <?= csrf_field() ?>
            <input type="hidden" name="submission_type" id="submission_type" value="tip">

            <div class="form-group">
                <label class="form-label" for="title">Subject</label>
                <input type="text" name="title" id="title" class="form-input" required placeholder="Brief description of what you're sharing">
            </div>

            <div class="form-group">
                <label class="form-label" for="body">Details</label>
                <textarea name="body" id="body" class="form-textarea" rows="8" required placeholder="Tell us what you know. Include dates, names, schools, and any relevant context."></textarea>
                <span class="form-helper">Your information will be kept confidential. We'll never share your identity without permission.</span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="submitter_name">Your Name <span style="color:var(--text-muted);">(optional)</span></label>
                    <input type="text" name="submitter_name" id="submitter_name" class="form-input" placeholder="Name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="submitter_email">Your Email <span style="color:var(--text-muted);">(optional)</span></label>
                    <input type="email" name="submitter_email" id="submitter_email" class="form-input" placeholder="email@example.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="submitter_org">District / Organization</label>
                <input type="text" name="submitter_org" id="submitter_org" class="form-input" placeholder="Which school district or organization is this about?">
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</section>

<script>
function showTab(tab) {
    document.querySelectorAll('.submit-tab').forEach(function(t) { t.classList.remove('active'); });
    event.target.classList.add('active');
    document.getElementById('submission_type').value = tab;
    var hints = {
        tip: 'Your information will be kept confidential.',
        help: 'Describe your situation and what kind of help you need.',
        upload: 'We accept PDFs, Word documents, spreadsheets, and images.'
    };
    document.querySelector('.form-helper').textContent = hints[tab] || hints.tip;
}
</script>
