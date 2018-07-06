{"version":3,"sources":["range_field.js"],"names":["BX","namespace","Landing","UI","Field","Range","data","this","items","type","isArray","values","Collection","BaseCollection","isMultiple","inputInner","createInputInner","container","create","props","className","output","createOutput","sliderFrom","sliderTo","createSlider","sliderValue","createValue","elements","frame","format","postfix","changeHandler","onChange","onValueChangeHandler","onValueChange","dragStartHandler","onDragStart","dragEndHandler","onDragEnd","jsDD","window","top","value","valueFrom","valueTo","BaseField","apply","arguments","layout","classList","add","stepPercent","length","content","undefined","from","to","setValue","prototype","constructor","__proto__","init","input","appendChild","forEach","item","index","valuePercent","valueToPercent","left","valueToLeftPercent","name","onbxdragstart","bind","onbxdrag","onDrag","onbxdragstop","registerObject","requestAnimationFrame","style","transform","onFrameLoad","outputInput","text","children","events","click","onArrowUpClick","onArrowDownClick","result","i","slice","call","document","querySelectorAll","selector","element","find","contains","getStartXOffset","parseFloat","current_node","getBoundingClientRect","width","pageXOffset","offset","x","xx","pip","sliderLeft","lastPos","filter","getValue","property","toPercent","fromPercent","preventEvent","updateValuePosition","Math","min","right","max","innerText","createInput","isChanged","JSON","stringify"],"mappings":"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","file":""}