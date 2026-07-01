// ── Simple fade helpers ────────────────────────────────────────────────────────
function fadeOut(el, ms = 250, cb) {
	el.style.transition = `opacity ${ms}ms`;
	el.style.opacity = '0';
	setTimeout(() => {
		el.style.display = 'none';
		el.style.transition = '';
		if (cb) cb();
	}, ms);
}

function fadeIn(el, ms = 250, display = 'inline') {
	el.style.display = display;
	el.style.opacity = '0';
	el.style.transition = `opacity ${ms}ms`;
	requestAnimationFrame(() => {
		requestAnimationFrame(() => { el.style.opacity = '1'; });
	});
	setTimeout(() => { el.style.transition = ''; }, ms);
}

// ── delete-in-place (vanilla rewrite, ported from EdgeCart store/js/app.js) ─────
class DeleteInPlace extends HTMLElement {
	connectedCallback() {
		if (this._initialized) return;
		this._initialized = true;

		const caption = this.getAttribute('caption') || 'delete';
		const confirm = this.getAttribute('confirm') || 'are you sure?';

		const params = {};
		for (const attr of this.attributes) {
			if (!['caption', 'confirm', 'class'].includes(attr.name)) {
				params[attr.name] = attr.value;
			}
		}

		this.innerHTML = `
			<span style="position:relative;display:inline-block;">
				<a class="dip-delete" style="cursor:pointer;">${caption}</a>
				<a class="dip-confirm" style="color:green;cursor:pointer;display:none;">${confirm}</a>
			</span>`;

		this._timer  = null;
		this._delEl  = this.querySelector('.dip-delete');
		this._conEl  = this.querySelector('.dip-confirm');

		this._delEl.addEventListener('click', () => {
			fadeOut(this._delEl, 250, () => fadeIn(this._conEl, 250));
			this._timer = setTimeout(() => this._reset(), 5000);
		});

		this._conEl.addEventListener('click', () => {
			clearTimeout(this._timer);
			this.dispatchEvent(new CustomEvent('dip-confirm', { bubbles: true, detail: params }));
			this._reset();
		});
	}

	_reset() {
		clearTimeout(this._timer);
		fadeOut(this._conEl, 250, () => fadeIn(this._delEl, 250));
	}
}
customElements.define('delete-in-place', DeleteInPlace);
