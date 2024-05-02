import "./scss/be.scss"
import LanguageSelection from "./js/backend/language-selection"
import MultilangTable from "./js/backend/multilang-table";
// import TableVariants from "./js/backend/table-variants";
// import FileField from "./js/backend/file-field";

document.addEventListener('DOMContentLoaded', ()=>{
    LanguageSelection.bindElements()
    MultilangTable.bindElements()
    // TableVariants.bindElements()
})
