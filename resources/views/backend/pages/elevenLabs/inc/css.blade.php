<style>
/*Range style*/
.range-slider__range {
  appearance: none;
  width: calc(100% - (53px));
  height: 6px;
  border-radius: 5px;
  background: #d7dcdf; 
  outline: none;
  padding: 0;
  margin: 0;
}

/*Range black ⚫ thumb*/
.range-slider__range::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 100%;
  background: #ff8f3b;
  cursor: pointer;
  transition: all 0.3s ease-in-out;
}

/*On hover change colour of black thumb into green 🟢 and scale size*/
.range-slider__range::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  background: #ff8f3b;}

.range-slider__range:active::-webkit-slider-thumb {
  transform: scale(1.1);
  background: #ff8f3b;}

/*Range current value*/
.range-slider__value {
  display: inline-block;
  position: relative;
  width: 40px;
  line-height: 20px;
  text-align: center;
  border-radius: 3px;
  background: #d7dcdf;
  margin-left: 8px;
  font-size: 12px
}

.range-slider__value:after {
  position: absolute;
  top: 3px;
  left: -7px;
  width: 0;
  height: 0;
  border-top: 7px solid transparent;
  border-right: 7px solid #d7dcdf;
  border-bottom: 7px solid transparent;
  content: "";
}

</style>