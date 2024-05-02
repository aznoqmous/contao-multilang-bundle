export default class LanguageSelection {
    constructor(container) {
        this.container = container
        this.build()
        this.bind()
    }

    get value() {
        return this.input.value.split(',')
    }

    set value(arrLangKey) {
        this.input.value = arrLangKey.join(',')
    }

    build() {
        this.input = this.container.querySelector('input[name="languages"]')
        this.availableLanguagesContainer = this.container.querySelector('.available-languages')
        this.selectedLanguagesContainer = this.container.querySelector('.selected-languages')
        this.languages = [...this.container.querySelectorAll('[data-lang]')]
        this.searchInput = this.container.querySelector('input[type="text"]')
    }

    bind() {
        this.languages.map(el => {
            el.querySelector('.add').addEventListener('click', () => {
                this.addLanguage(el)
            })
            el.querySelector('.remove').addEventListener('click', () => {
                this.removeLanguage(el)
            })
            el.querySelector('.default').addEventListener('click', () => {
                this.setDefaultLanguage(el)
            })
        })

        this.availableLanguagesContainer.style.display = 'none'
        this.searchInput.addEventListener('focus', () => {
            this.availableLanguagesContainer.style.display = null
        })
        window.addEventListener('click', (e)=>{
            if(!this.container.contains(e.target)) this.availableLanguagesContainer.style.display = 'none'
        })
        this.searchInput.addEventListener('input', () => {
            this.filter()
        })
    }

    addLanguage(el) {
        this.selectedLanguagesContainer.appendChild(el)
        this.refreshValue()
    }

    removeLanguage(el) {
        this.availableLanguagesContainer.appendChild(el)
        this.refreshValue()
    }

    setDefaultLanguage(el) {
        this.selectedLanguagesContainer.insertBefore(el, this.selectedLanguagesContainer.children[0])
        this.refreshValue()
    }

    refreshValue() {
        this.value = [...this.selectedLanguagesContainer.children].map(el => el.dataset.lang)
    }

    filter() {
        let regex = new RegExp(this.searchInput.value, 'i')
        this.languages.map(el => el.style.display = null)
        ;[...this.availableLanguagesContainer.children]
            .filter(el => !el.innerText.match(regex))
            .map(el => el.style.display = 'none')
    }

    static bindElements() {
        [...document.querySelectorAll('.be_lang_selection_wizard')]
            .map(el => new LanguageSelection(el))
    }
}
