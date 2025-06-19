const template = (body) => ` <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        <link href="https://unpkg.com/tailwindcss/dist/tailwind.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>body {
          font-family: 'Open Sans', sans-serif;
      }
      
      .task-card {
          display: flex;
          background: white;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
          border-radius: 0.25rem;
          flex-direction: column;
          margin-bottom: 1rem;
          gap: 1rem;
      }
      
      .task-image-container {
          position: relative;
          max-width: none;
          overflow: hidden;
          border-radius: 0.25rem;
          cursor: pointer;
      
          .excluded {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              backdrop-filter: blur(5px);
              background-color: rgba(0, 0, 0, 0.72);
              z-index: 1;
      
              /* Add these lines */
              display: flex;
              justify-content: center;
              align-items: center;
              text-align: center;
          }
      }
      
      .task-image {
          width: 100%;
          height: 100%;
          display: block;
          object-fit: cover;
      }
      
      
      .icon-container {
          position: absolute;
          top: 6px;
          left: 6px;
          margin-top: 12px;
          margin-left: 12px;
          display: flex;
          -webkit-box-align: center;
          align-items: center;
          -webkit-box-pack: center;
          justify-content: center;
          width: 24px;
          height: 24px;
          background: white;
          border-radius: 4px;
          z-index: 2;
      
          .icon-box {
              font-weight: 700;
              font-size: 12px;
              color: black;
              line-height: 1px;
              margin: 0px;
          }
      }
      
      .icon-container.right {
          left: unset;
          margin-left: unset;
          right: 6px;
          cursor: pointer;
          margin-right: 12px;
      }
      
      
      .task-content {
          display: flex;
          flex-direction: column;
          flex-grow: 1;
          width: 100%;
          padding: 0.5rem;
          font-size: 0.875rem;
          color: #333;
      }
      
      .task-text {
          overflow: hidden;
          text-overflow: ellipsis;
          display: -webkit-box;
          -webkit-line-clamp: 4;
          /* Limit text to 4 lines */
          -webkit-box-orient: vertical;
      
          &.no-overflow {
              overflow: visible;
              text-overflow: unset;
              display: block;
              -webkit-line-clamp: unset;
              -webkit-box-orient: unset;
          }
      }
      
      .task-footer {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          align-items: flex-start;
          padding: 0.5rem;
          font-size: 0.75rem;
          color: #4b5563;
          border-top: 1px solid #e5e7eb;
          width: 100%;
          margin-top: auto;
      
      
          .task-footer-column {
              display: flex;
              align-items: center;
              margin-top: 0.5rem;
          }
      
          .tags-container {
              display: flex;
              align-items: center;
              gap: 0.5rem;
          }
      }
      
      
      .task-footer-icon {
          margin-right: 0.25rem;
      }
      
      .task-footer .location {
          font-weight: 600;
      }
      
      /* Media queries for common breakpoints */
      @media (min-width: 576px) {
      
          /* Small devices (landscape phones, 576px and up) */
          .task-card {
              flex-direction: row;
          }
      
          .task-image-container {
              width: 300px;
          }
      
          .task-content {
              flex-grow: 1;
          }
      }
      
      @media (min-width: 768px) {
      
          /* Medium devices (tablets, 768px and up) */
          .task-image-container {
              width: 300px;
          }
      }
      
      @media (min-width: 992px) {
      
          /* Large devices (desktops, 992px and up) */
          .task-image-container {
              width: 300px;
          }
      }
      
      @media (min-width: 1200px) {
      
          /* Extra large devices (large desktops, 1200px and up) */
          .task-image-container {
              width: 100%;
          }
      } </style>
    </head>
    <body>
        ${body}
    </body>
    </html>`;
module.exports = { template };