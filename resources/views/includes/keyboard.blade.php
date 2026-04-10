<!-- simple-keyboard CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/css/index.css">

<style>
    .keyboard-wrapper {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: #ececec;
        padding: 10px;
        box-shadow: 0 -5px 15px rgba(0,0,0,0.1);
        z-index: 9999;
        display: none; /* Hidden by default */
        transition: transform 0.3s ease-in-out;
        transform: translateY(100%);
    }

    .keyboard-wrapper.show {
        display: block;
        transform: translateY(0);
    }

    /* Customizing simple-keyboard for a cleaner look */
    .simple-keyboard {
        max-width: 1000px;
        margin: 0 auto;
        background-color: transparent;
        font-family: inherit;
    }

    .hg-theme-default .hg-button {
        height: 50px;
        font-size: 1.1rem;
        font-weight: 500;
        border-radius: 8px;
        background: #fff;
        border-bottom: 3px solid #ccc;
    }

    .hg-theme-default .hg-button:active {
        background: #f0f0f0;
        border-bottom: 1px solid #ccc;
    }

    .close-keyboard {
        position: absolute;
        top: -40px;
        right: 20px;
        background: #1b5e20;
        color: white;
        border: none;
        padding: 5px 15px;
        border-radius: 10px 10px 0 0;
        font-weight: bold;
        cursor: pointer;
    }
</style>

<div class="keyboard-wrapper" id="keyboard-container">
    <button class="close-keyboard" onclick="hideKeyboard()">DONE</button>
    <div class="simple-keyboard"></div>
</div>

<!-- simple-keyboard JS -->
<script src="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/index.js"></script>

<script>
    let keyboard;
    let selectedInput;
    let lastFocusTime = 0;

    document.addEventListener("DOMContentLoaded", () => {
        const Keyboard = window.SimpleKeyboard.default;

        keyboard = new Keyboard({
            onChange: input => onChange(input),
            onKeyPress: button => onKeyPress(button),
            theme: "hg-theme-default",
            layout: {
                default: [
                    "q w e r t y u i o p {bksp}",
                    "a s d f g h j k l {enter}",
                    "{shift} z x c v b n m , . /",
                    "{numbers} {space} {close}"
                ],
                shift: [
                    "Q W E R T Y U I O P {bksp}",
                    "A S D F G H J K L {enter}",
                    "{shift} Z X C V B N M ! ? /",
                    "{numbers} {space} {close}"
                ],
                numbers: [
                    "1 2 3 4 5 6 7 8 9 0 {bksp}",
                    "- / : ; ( ) $ & @ \" {enter}",
                    "{default} . , ? ! '",
                    "{default} {space} {close}"
                ]
            },
            display: {
                "{bksp}": "⌫",
                "{enter}": "Enter",
                "{shift}": "⇧",
                "{space}": "Space",
                "{default}": "ABC",
                "{numbers}": "123",
                "{close}": "Close"
            }
        });

        // Use event delegation for focus
        document.addEventListener("focusin", (event) => {
            if (
                event.target.tagName === "INPUT" && 
                ["text", "password", "email", "number", "tel", "search"].includes(event.target.type)
            ) {
                onInputFocus(event);
            }
        });
    });

    function onInputFocus(event) {
        lastFocusTime = Date.now();
        selectedInput = event.target;
        keyboard.setOptions({
            inputName: event.target.name
        });
        
        // Sync keyboard value with current input value
        keyboard.setInput(event.target.value);
        
        showKeyboard();
    }

    function onChange(input) {
        if (selectedInput) {
            selectedInput.value = input;
            
            // Trigger input event for frameworks like Alpine or Vue
            selectedInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function onKeyPress(button) {
        if (button === "{shift}" || button === "{lock}") handleShift();
        if (button === "{numbers}" || button === "{default}") handleLayout(button);
        if (button === "{close}") hideKeyboard();
        if (button === "{enter}") {
            hideKeyboard();
        }
    }

    function handleShift() {
        let currentLayout = keyboard.options.layoutName;
        let shiftToggle = currentLayout === "default" ? "shift" : "default";

        keyboard.setOptions({
            layoutName: shiftToggle
        });
    }

    function handleLayout(button) {
        let layoutName = button === "{numbers}" ? "numbers" : "default";
        keyboard.setOptions({
            layoutName: layoutName
        });
    }

    function showKeyboard() {
        document.getElementById("keyboard-container").classList.add("show");
        document.body.style.paddingBottom = "250px"; 
    }

    function hideKeyboard() {
        document.getElementById("keyboard-container").classList.remove("show");
        document.body.style.paddingBottom = "0";
    }

    // Close keyboard if clicking outside of input and keyboard
    document.addEventListener("click", event => {
        // Ignore clicks that happen immediately after a focus event 
        // (to handle layout shifts misdirecting the click)
        if (Date.now() - lastFocusTime < 300) {
            return;
        }

        const isInput = event.target.tagName === "INPUT";
        const isKeyboard = event.target.closest(".keyboard-wrapper");
        
        if (!isInput && !isKeyboard) {
            hideKeyboard();
        }
    });
</script>
