const inputs = document.querySelectorAll(".otp-input");
const hiddenInput = document.getElementById("verification_code");

inputs.forEach((input,index)=>{

    input.addEventListener("input",()=>{

        if(input.value.length===1 && index<5){

            inputs[index+1].focus();

        }

        updateHidden();

    });

    input.addEventListener("keydown",(e)=>{

        if(e.key==="Backspace" && input.value==="" && index>0){

            inputs[index-1].focus();

        }

    });

});

function updateHidden(){

    let code="";

    inputs.forEach(input=>{

        code += input.value;

    });

    hiddenInput.value=code;

}