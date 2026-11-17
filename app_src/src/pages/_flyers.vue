<template>
  <svg
    id="affiche"
    font-family="Cooper Hewitt"
    font-size="10"
    line
    text-anchor="middle"
    :viewBox="`0 0 ${size[0]} ${size[1]}`"
    xmlns="http://www.w3.org/2000/svg"
  >
    <component :is="'style'" id="style">
      @media print {
      @page {
      size: {{ size[0] }}mm {{ size[1] }}mm;
      }
      * {
      margin: 0;
      padding: 0;
      }
      #affiche {
      display: block;
      box-sizing: border-box;
      max-height: 100%;
      min-height: 100%;
      height: 100%;
      }
      .margin {
      display: none;
      }
      }
    </component>

    <filter id="shadow">
      <feDropShadow dx="1" dy="1" flood-color="#00000088" stdDeviation="1" />
    </filter>

    <image
      :height="affiche.img.size"
      :href="affiche.img.value || imgPlaceholder"
      :width="affiche.img.size"
      :x="affiche.img.x - (affiche.img.size / 2)"
      :y="affiche.img.y - (affiche.img.size / 2)"
      @click="current = 'img'"
      @mousemove="drag"
    />

    <text id="textTop" x="0" y="10">
      <tspan
        id="catchphrase"
        :fill="affiche.catchphrase.color"
        font-weight="713"
        @click="current = 'catchphrase'"
      >
        <tspan
          v-for="line of affiche.catchphrase.value.split('\n')"
          :key="line"
          dy="1em"
          font-size="45"
          x="50%"
        >
          {{ line }}
        </tspan>
      </tspan>

      <tspan
        :fill="affiche.subTitle.color"
        font-weight="711"
        @click="current = 'subTitle'"
      >
        <tspan
          v-for="line of affiche.subTitle.value.split('\n')"
          :key="line"
          dy="1.2em"
          x="50%"
        >
          {{ line }}
        </tspan>
      </tspan>

      <tspan
        :fill="affiche.text.color"
        font-weight="709"
        @click="current = 'text'"
      >
        <tspan
          v-for="line of affiche.text.value.split('\n')"
          :key="line"
          dy="1.2em"
          x="50%"
        >
          {{ line }}
        </tspan>
      </tspan>
    </text>

    <rect
      :fill="affiche.highlight.color"
      filter="url(#shadow)"
      :height="textBottomBox.height + 10"
      :width="textBottomBox.width + 20"
      :x="textBottomBox.x - 10"
      :y="textBottomBox.y - 6"
    />

    <text id="textBottom" x="50%" :y="size[1] - 17">
      <tspan
        fill="white"
        font-weight="711"
        @click="current = 'highlight'"
      >
        {{ affiche.highlight.value }}
      </tspan>
    </text>

    <rect
      class="margin"
      fill="none"
      :height="size[1] - 20"
      stroke="magenta"
      stroke-width=".1"
      :width="size[0] - 20"
      x="10"
      y="10"
    />
  </svg>

  <v-container>
    <v-row v-if="current">
      <v-col :cols="current == 'img' ? 3 : 9">
        <v-textarea v-model="affiche[current].value" :label="current" />
        <input v-if="current == 'img'" type="file" @change="uploadImage">
      </v-col>

      <v-col v-if="current != 'img'" cols="3">
        <v-select
          v-model="affiche[current].color"
          :class="`bg-${availableColors[affiche[current].color].title}`"
          :item-props="true"
          :items="Object.values(availableColors)"
          label="Couleur"
        />
      </v-col>

      <template v-else>
        <v-col cols="3">
          <v-number-input v-model="affiche.img.size" label="Taille" />
        </v-col>

        <v-col cols="3">
          <v-number-input v-model="affiche.img.x" label="X" />
        </v-col>

        <v-col cols="3">
          <v-number-input v-model="affiche.img.y" label="Y" />
        </v-col>
      </template>
    </v-row>
  </v-container>

  <v-btn @click="execPrint" @mousedown="genSvgHref">Imprimer</v-btn>

  <iframe v-show="false" id="printFrame" :srcdoc="svgText" />
</template>

<script lang="ts" setup>
  import { nextTick, reactive, ref, watch } from 'vue'
  import { useTheme } from 'vuetify'

  const colors = useTheme().current.value.colors
  colors.noir = '#000000'
  const availableColors = ref([])
  for (const title of ['noir', 'savon', 'electronique', 'couture', 'mosaique', 'bois', 'cuir', 'velo', 'tricot']) {
    availableColors.value[colors[title]] = { title, value: colors[title], class: `bg-${title}` }
  }

  const size = ref([297, 210])
  const current = ref()
  const textTopBox = ref({ x: 0, y: 0, width: 0, height: 0 })
  const textBottomBox = ref({ x: 0, y: 0, width: 0, height: 0 })
  const svgText = ref('')
  const imgPlaceholder = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEBLAEsAAD/4QBWRXhpZgAATU0AKgAAAAgABAEaAAUAAAABAAAAPgEbAAUAAAABAAAARgEoAAMAAAABAAIAAAITAAMAAAABAAEAAAAAAAAAAAEsAAAAAQAAASwAAAAB/+0ALFBob3Rvc2hvcCAzLjAAOEJJTQQEAAAAAAAPHAFaAAMbJUccAQAAAgAEAP/hDW5odHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvADw/eHBhY2tldCBiZWdpbj0n77u/JyBpZD0nVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkJz8+Cjx4OnhtcG1ldGEgeG1sbnM6eD0nYWRvYmU6bnM6bWV0YS8nIHg6eG1wdGs9J0ltYWdlOjpFeGlmVG9vbCAxMS44OCc+CjxyZGY6UkRGIHhtbG5zOnJkZj0naHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyc+CgogPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9JycKICB4bWxuczp0aWZmPSdodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyc+CiAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICA8dGlmZjpYUmVzb2x1dGlvbj4zMDAvMTwvdGlmZjpYUmVzb2x1dGlvbj4KICA8dGlmZjpZUmVzb2x1dGlvbj4zMDAvMTwvdGlmZjpZUmVzb2x1dGlvbj4KIDwvcmRmOkRlc2NyaXB0aW9uPgoKIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PScnCiAgeG1sbnM6eG1wPSdodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvJz4KICA8eG1wOkNyZWF0b3JUb29sPkFkb2JlIFN0b2NrIFBsYXRmb3JtPC94bXA6Q3JlYXRvclRvb2w+CiA8L3JkZjpEZXNjcmlwdGlvbj4KCiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0nJwogIHhtbG5zOnhtcE1NPSdodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vJz4KICA8eG1wTU06RG9jdW1lbnRJRD54bXAuaWlkOjNhZmI1MjAzLTBlZDctNDUxMC05OWUzLTczM2FjYTU5MDU2YTwveG1wTU06RG9jdW1lbnRJRD4KICA8eG1wTU06SW5zdGFuY2VJRD5hZG9iZTpkb2NpZDpzdG9jazo1NzEzZmQ1ZC01ZGE0LTRhNzktOGVlZi0yMjI1MWZiZWU1MjY8L3htcE1NOkluc3RhbmNlSUQ+CiAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD5hZG9iZTpkb2NpZDpzdG9jazo2NTczNzAxNTA8L3htcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD4KIDwvcmRmOkRlc2NyaXB0aW9uPgo8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAo8P3hwYWNrZXQgZW5kPSd3Jz8+/9sAQwAF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U+gAAAAAAAAAAAAAXrpUBeuldoC9dK7QF66V2gL10rtAXrpXaAvXSu0BeuldoC9dK7QF66V2gL10rtAXrpXaAvXSu0BeuldoC9dK7QF66V2gL10rtAXrpXaAvXSu0BeuldoC9dK7QF66V2gL10rtAXrpXaAvXSu0BeuldoC9dK7QF66V2gL10rtAXrpXaAvXSoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/9k='
  const affiche = reactive({
    catchphrase: { color: colors.tricot, value: '« A-AA-AAA-AAAA\nTCHOUM !!! »' },
    subTitle: { color: colors.couture, value: 'LA POUSSIÈRE DE BOIS EST UN CACÉROGÈNE DE GROUPE 1' },
    text: { color: colors.noir, value: 'Protèges la prunelle de tes yeux.' },
    highlight: { color: colors.velo, value: 'Porte des lunettes' },
    img: { x: size.value[0] / 2, y: size.value[1] / 2, size: 75, value: '' },
  })

  watch(affiche.catchphrase, async () => {
    await nextTick()
    const text = document.querySelector('#affiche #catchphrase')
    const lines = [...text.children]
    for (const span of lines) {
      const attr = span.getAttributeNode('font-size')
      while (attr.textContent < 45 && span.getBBox().width < size.value[0] - 20) {
        attr.textContent = (attr.textContent * 1) + 0.1
      }
      while (attr.textContent > 1 && span.getBBox().width > size.value[0] - 20) {
        attr.textContent = (attr.textContent * 1) - 0.1
      }
    }
    let height = lines.reduce((acc, val) => acc + val.getBBox().height, 0)
    console.log(height)
    while (height > size.value[1] / 10 * 3) {
      for (const span of lines.slice(1)) {
        const attr = span.getAttributeNode('font-size')
        attr.textContent = (attr.textContent * 1) - 0.1
      }
      height = lines.reduce((acc, val) => acc + val.getBBox().height, 0)
    }
  })

  watch(affiche.highlight, async () => {
    await nextTick()
    const textTop = document.querySelector('#affiche #textTop')
    const textBottom = document.querySelector('#affiche #textBottom')
    textTopBox.value = textTop.getBBox()
    textBottomBox.value = textBottom.getBBox()
  }, { immediate: true })

  async function execPrint () {
    await nextTick()
    svgText.value = document.querySelector('#affiche').outerHTML
    const iframe = document.querySelector('#printFrame')
    iframe.addEventListener('load', () => {
      iframe.contentWindow.print()
    })
  }

  async function genSvgHref () {
    // await nextTick()
    // const svgNode = document.querySelector('#affiche')
    // svgText.value = svgNode.outerHTML
  }

  function drag (evt) {
    if (evt.buttons == 1) {
      const svg = document.querySelector('#affiche')
      const bBox = svg.getBoundingClientRect()
      const scale = size.value[0] / document.querySelector('#affiche').clientWidth
      affiche.img.x = (evt.clientX - bBox.x) * scale
      affiche.img.y = (evt.clientY - bBox.y) * scale
      const centerX = size.value[0] / 2
      const centerY = (textTopBox.value.y + textTopBox.value.height + textBottomBox.value.y - 10) / 2
      if (centerX - 10 <= affiche.img.x && affiche.img.x <= centerX + 10) {
        affiche.img.x = centerX
      }
      console.log(textBottomBox.value.y)
      if (centerY - 10 <= affiche.img.y && affiche.img.y <= centerY + 10) {
        affiche.img.y = centerY
      }
    }
  }

  function convertBase64 (file) {
    return new Promise((resolve, reject) => {
      const fileReader = new FileReader()
      fileReader.readAsDataURL(file)

      fileReader.addEventListener('load', () => {
        resolve(fileReader.result)
      })

      fileReader.addEventListener('error', error => {
        reject(error)
      })
    })
  }

  async function uploadImage (evt) {
    affiche.img.value = await convertBase64(evt.target.files[0])
  }
</script>

<style>
#affiche {
    border: 1px solid black;
    user-select: none;

    text > tspan:hover {
        cursor: pointer;
        fill: lightcyan;
    }

    image {
        cursor: grab;

        &:active {
            cursor: grabbing;
        }
    }
}
</style>
