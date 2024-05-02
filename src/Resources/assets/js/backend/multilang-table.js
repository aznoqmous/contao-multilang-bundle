export default class MultilangTable {

    constructor(container) {
        this.container = container
        this.build()
        this.bind()
    }
    build(){
        this.button = this.container.querySelector(".tl_submit")
        this.select = this.container.querySelector('[name="lang"]')
    }
    bind(){
        if(this.button) this.button.addEventListener('click', ()=>{
            this.send(this.select.value, this.container.dataset.table)
        })
    }

    send(lang, table){
        let url = `/api/multilang/set/${lang}`
        if(table) url += "/"+table
        return fetch(url)
            .then(res => res.json())
            .then(res => {
                window.location.reload()
            })
    }

    static bindElements(){
        [...document.querySelectorAll('.be_multilang_tables_wizard [data-table]')]
            .map(el => new MultilangTable(el))
    }

}
