export default class TableVariants {
    constructor(container) {
        this.container = container
        this.elements = {}
        this.table = document.querySelector('[data-active-table]')
        this.activeLanguage = document.querySelector('[data-active-language]')
        if (!this.table) return null;
        this.table = this.table.dataset.activeTable
        this.activeLanguage = this.activeLanguage.dataset.activeLanguage
        this.getLanguages()
        .then(()=>{
            this.build()
        })
    }

    getLanguages(){
        return fetch("/api/multilang/languages")
            .then(res => res.json())
            .then(languages => this.languages = languages)
    }

    build() {
        [...this.container.querySelectorAll('.tl_file,.tl_folder,tr,.tl_content')]
            .map(el => new TableEntity(el))
            .filter(t => t.build())
            .map(t => {
                this.elements[t.id] = t
            })
        if (!this.elements) return;

        let body = new FormData()
        body.append('ids', Object.keys(this.elements).join(','))
        fetch(`/api/multilang/variants/${this.table}`, {
            method: "POST",
            body
        })
            .then(res => res.json())
            .then(variantsById => {
                this.languages.reverse()
                this.languages = this.languages.filter(l => l.key != this.activeLanguage)
                Object.values(this.elements).map(el => {
                    this.languages.map(language => {
                        let variants = variantsById[el.id]
                        let hasLang = variants.filter(v => v.lang === language.key).length
                        el.buildLang(language, hasLang)
                    })
                    // el.editLink.remove()
                })

            })
    }

    static bindElements() {
        return [...document.querySelectorAll('#tl_listing')]
            .map(el => new TableVariants(el))
    }
}

class TableEntity {
    constructor(container) {
        this.container = container
    }

    build() {
        this.labelContainer = this.container.children[0]

        this.editHeaderLink = this.container.querySelector('a.editheader')
        if(this.editHeaderLink) this.editHeaderHref = this.editHeaderLink.href

        this.editLink = this.container.querySelector('a.edit')
        if (!this.editLink) return null;
        this.actionsContainer = this.editLink.parentElement
        let url = new URL(this.editLink.href)
        this.id = url.searchParams.get('id')
        this.editHref = this.editLink.href
        return !!this.id
    }

    buildLang(lang, variantExists) {
        let variantEl = document.createElement('span')
        variantEl.classList.add('table-lang-variant')
        if(!variantExists) variantEl.classList.add('create')
        variantEl.innerHTML = `<a href="${this.getLangEditHref(lang, variantExists)}" title="${lang.label}"><img src="${lang.imagePath}" alt="${lang.label}"></a>`
        // this.actionsContainer.insertBefore(variantEl, this.editLink)
        let nextLink = this.actionsContainer.children[[...this.actionsContainer.children].indexOf(this.editLink)+1]
        if(nextLink) this.actionsContainer.insertBefore(variantEl, nextLink)
        else this.actionsContainer.appendChild(variantEl)
    }

    getLangEditHref(lang, variantExists) {
        let href = !variantExists && this.editHeaderHref ? this.editHeaderHref : this.editHref;
        let url = new URL(href)
        url.searchParams.set('lang', lang.key)
        return url.href
    }
}
