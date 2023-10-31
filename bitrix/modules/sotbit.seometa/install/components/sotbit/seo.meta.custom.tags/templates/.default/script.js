window.addEventListener("DOMContentLoaded", function () {
    if (document.querySelector(".sotbit-seometa-tags__hide")) {
        let hideTagsBtns = document.querySelector(".sotbit-seometa-tags__hide");

        hideTagsBtns.addEventListener("click", function () {
            replaceClass();
        });

        hideSeoMetaTags();
    }
});

window.addEventListener("resize", function () {
    if (document.querySelector(".sotbit-seometa-tags__hide")) {
        collapseTags();
        hideSeoMetaTags();
    }
});

function hideSeoMetaTags() {
    if (document.querySelector(".sotbit-seometa-tags-column")) {
        let seoMetaBlock = document.querySelector(".sotbit-seometa-tags-column"),
            hideTagsBtns = seoMetaBlock.querySelector(".sotbit-seometa-tags__hide"),
            tagsSection = seoMetaBlock.querySelector(".tags_section"),
            windowWidth = window.innerWidth || document.documentElement.clientWidth,
            tagsWrapper = seoMetaBlock.querySelector(".tags_wrapper");

        if (windowWidth > 480) {
            if (tagsSection.clientHeight > 88) {
                setSeometaClass("inline-block");
                tagsWrapper.style.height = "88px"
            } else {
                hideTagsBtns.style.display = "none";
            }
        } else {
            if (tagsSection.clientHeight > 176) {
                setSeometaClass("flex");
                tagsWrapper.style.height = "176px"
            } else {
                hideTagsBtns.style.display = "none";
            }
        }
    }
}

function setSeometaClass(buttonDislpay) {
    let seoMetaBlock = document.querySelector(".sotbit-seometa-tags-column"),
        hideTagsBtns = seoMetaBlock.querySelector(".sotbit-seometa-tags__hide");
    let seoMetaClass = seoMetaBlock.getAttribute("class");

    seoMetaClass = seoMetaClass.replace(" closed", "");
    seoMetaClass = seoMetaClass.replace(" opened", "");
    seoMetaClass = seoMetaClass + " closed";

    seoMetaBlock.setAttribute("class", seoMetaClass);
    hideTagsBtns.style.display = buttonDislpay;
}

function replaceClass() {
    let seoMetaBlock = document.querySelector(".sotbit-seometa-tags-column");
    let seoMetaClass = seoMetaBlock.getAttribute("class");
    let tagsWrapper = seoMetaBlock.querySelector(".tags_wrapper");
    let tagsSection = seoMetaBlock.querySelector(".tags_section"),
        windowWidth = window.innerWidth || document.documentElement.clientWidth;

    if (seoMetaClass.includes("closed")) {
        seoMetaClass = seoMetaClass.replace("closed", "opened");

        seoMetaBlock.setAttribute("class", seoMetaClass);

        tagsWrapper.style.height = tagsSection.clientHeight + "px";

    } else if (seoMetaClass.includes("opened")) {

        seoMetaClass = seoMetaClass.replace("opened", "closed");

        seoMetaBlock.setAttribute("class", seoMetaClass);

        if (windowWidth > 480) {
            tagsWrapper.style.height = "88px";
        } else {
            tagsWrapper.style.height = "176px";
        }
    }
}

function collapseTags() {
    let seoMetaBlock = document.querySelector(".sotbit-seometa-tags-column");
    let seoMetaClass = seoMetaBlock.getAttribute("class");
    let tagsWrapper = seoMetaBlock.querySelector(".tags_wrapper");
    let windowWidth = window.innerWidth || document.documentElement.clientWidth;

    if (seoMetaClass.includes("opened")) {

        seoMetaClass = seoMetaClass.replace("opened", "closed");

        seoMetaBlock.setAttribute("class", seoMetaClass);

        if (windowWidth > 480) {
            tagsWrapper.style.height = "88px";
        } else {
            tagsWrapper.style.height = "176px";
        }
    }
}
