// First install: npm install @sendgrid/mail
const sgMail = require('@sendgrid/mail');

// Configure SendGrid with the API key
sgMail.setApiKey('SK29dafeae515161eaf5fffceeba535478');

// Email configuration
const emailConfig = {
    from: {
        email: 'mhchemedu@gmail.com',
        name: 'MHchem'
    },
    to: 'cocpissa12@gmail.com', // Recipient email
    subject: 'Test Email from SendGrid',
    text: 'This is a test email sent using SendGrid',
    html: `
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <h2>Test Email</h2>
            <p>Hello!</p>
            <p>This is a test email sent using SendGrid from your application.</p>
            <p>If you received this email, your SendGrid configuration is working correctly.</p>
            <br>
            <p>Best regards,</p>
            <p>MHchem</p>
        </div>
    `
};

// Function to send test email
async function sendTestEmail() {
    try {
        console.log('Attempting to send test email...');
        await sgMail.send(emailConfig);
        console.log('Test email sent successfully!');
    } catch (error) {
        console.error('Error sending test email:');
        console.error(error);
        
        if (error.response) {
            console.error('SendGrid API Error:');
            console.error(error.response.body);
        }
    }
}

// Execute the test
sendTestEmail();