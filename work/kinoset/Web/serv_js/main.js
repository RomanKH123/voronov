//Функция выхода из профиля
function ExitProf(){
    localStorage.clear();
    window.location.href = "/index.html";
  }
//Функция входа в систему
async function User_In(a) {
  let res = await fetch ('http://kinoset/User_up/'+ a);
    let posts = await res.json();
    //Распаковка гета
        
    localStorage.setItem('Idsdw', posts.ID_user);
    localStorage.setItem('Logina2f', posts.Login);
    window.location.href = "/index.html";
}
//Обрабатываем POST
const urlParams = new URLSearchParams(window.location.search);
const Id = urlParams.get('id');
if (Id !== null){
  User_In(Id)
}

async function searchByName() {
  const input = document.getElementById("poisk").value.toLowerCase();
  const items = document.getElementsByClassName("film");

  for (let i = 0; i < items.length; i++) {
    const nameAttr = items[i].getAttribute("name").toLowerCase();
    items[i].style.display = nameAttr.includes(input) ? "" : "none";
  }
}