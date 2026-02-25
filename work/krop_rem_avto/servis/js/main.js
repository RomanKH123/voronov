function slowScroll(id){
    $("html, body").animate({
        scrollTop: $(id).offset().top
    },300);
    return false;
}

function onEntry(entry) {
    entry.forEach(change => {
      if (change.isIntersecting) {
       change.target.classList.add('element-show');
      }
    });
  }
  
  let options = {
    threshold: [0.5] };
  let observer = new IntersectionObserver(onEntry, options);
  let elements = document.querySelectorAll('.shap_const_1');
  
  for (let elm of elements) {
    observer.observe(elm);
  }
  let elements_1 = document.querySelectorAll('.blok-time');
  
  
  for (let elm of elements_1) {
    observer.observe(elm);
  }
  let elements_2 = document.querySelectorAll('.blok-time-vs');
  
  
  for (let elm of elements_2) {
    observer.observe(elm);
  }