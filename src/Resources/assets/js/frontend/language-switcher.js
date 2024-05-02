export default class LanguageSwitcher {

    constructor(element) {
        this.element = element
        this.bind()
    }

    bind(){
        this.select = this.element.querySelector('.select')
        this.selected = this.select.querySelector('.selected')
        this.optionsContainer = this.select.querySelector('.options')
        this.options = [...this.optionsContainer.querySelectorAll('.option')]
        this.input = this.element.querySelector('[name="lang_select"]')

        this.select.addEventListener('click', ()=>{
            this.select.classList.toggle('active')
        })

        this.options.map(el => el.addEventListener('click', ()=>{
            this.selectOption(el)
        }))

        document.addEventListener('click', (e)=>{
            if(!this.element.contains(e.target)) this.select.classList.remove('active')
        })
    }

    selectOption(optionEl){
        this.selected.innerHTML = optionEl.innerHTML
        this.input.value = optionEl.getAttribute('data-value')
        this.element.submit()
    }

    static bindElements(){
        [...document.querySelectorAll('.mod_multilang_lang_select')]
            .map(el => new this(el))
    }
}
