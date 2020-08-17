/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2020  Stephen P Vickers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: stephen@spvsoftwareproducts.com
 */

var http_request = false;
var el_id;

function doApprove(id, url, ci, rlid) {
  getHTTPRequest()
  if (http_request) {
    var el = document.getElementById('button' + id);
    url += '?do=' + el.value + '&ci=' + escape(ci) + '&rlid=' + escape(rlid);
    el_id = id;
    http_request.onreadystatechange = alertApprove;
    http_request.open('GET', url, true);
    http_request.send(null);
  }
  return false;
}

function alertApprove() {
  if (http_request.readyState == 4) {
    if (http_request.status == 200) {
      var button = document.getElementById('button' + el_id);
      var label = document.getElementById('label' + el_id);
      if (button.value == 'Approve') {
        button.value = 'Suspend';
        label.innerHTML = 'Yes';
      } else {
        button.value = 'Approve';
        label.innerHTML = 'No';
      }
    }
  }
}

function doGenerateKey() {
  getHTTPRequest()
  if (http_request) {
    var url = 'generate.php';
    var el = document.getElementById('life');
    url += '?life=' + el.value;
    el = document.getElementById('auto_approve');
    if (el.checked) {
      url += '&auto_approve=yes';
    }
    http_request.onreadystatechange = alertGenerateKey;
    http_request.open('GET', url, true);
    http_request.send(null);
  }
  return false;
}

function alertGenerateKey() {
  if (http_request.readyState == 4) {
    if (http_request.status == 200) {
      var key = http_request.responseText;
      if (key.length > 0) {
        window.prompt('Send this share key string to the other instructor:', 'share_key=' + key);
      } else {
        alert('Sorry an error occurred in generating a new share key; please try again');
      }
    } else {
      alert('Sorry unable to generate a new share key');
    }
  }
}


function getHTTPRequest() {
  http_request = false;
  if (window.XMLHttpRequest) { // Mozilla, Safari,...
    http_request = new XMLHttpRequest();
  } else if (window.ActiveXObject) { // IE
    try {
      http_request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        http_request = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {
      }
    }
  }
}
