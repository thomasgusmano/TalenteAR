document.addEventListener("DOMContentLoaded", function () {
  var locationInput = document.querySelector(".search-input:nth-child(2)");
  var provinceList = document.getElementById("provinceList");
  var provinces = provinceList.getElementsByTagName("td");
  function toggleProvinceList(event) {
    provinceList.style.display =
      provinceList.style.display === "none" ? "block" : "none";
    event.stopPropagation();
  }
  function selectProvince(event) {
    var selectedProvince = event.target.innerText;
    locationInput.value = selectedProvince;
    provinceList.style.display = "none";
  }
  function filterProvinces() {
    var filter = locationInput.value.toUpperCase();
    for (var i = 0; i < provinces.length; i++) {
      var province = provinces[i];
      var provinceName = province.innerText.toUpperCase();
      if (provinceName.startsWith(filter)) {
        province.style.display = "";
      } else {
        province.style.display = "none";
      }
    }
  }
  function closeProvinceList() {
    provinceList.style.display = "none";
  }
  locationInput.addEventListener("click", toggleProvinceList);
  locationInput.addEventListener("input", filterProvinces);
  document.addEventListener("click", closeProvinceList);
  for (var i = 0; i < provinces.length; i++) {
    provinces[i].addEventListener("click", selectProvince);
  }
});
